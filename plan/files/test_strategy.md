# Test Strategy

## Philosophy

Test what can break in production. Don't test Laravel internals. Don't test getters.

Every test must answer: "does this behavior work correctly, and would I know if it broke?"

---

## Test Types and When to Use Each

### Feature Tests (HTTP layer)

Use for every endpoint. These are the most valuable tests in this project because:
- They verify the full request → response cycle
- They catch middleware issues (auth, permissions, scoping)
- They verify DB state after operations
- They run against a real DB (SQLite in-memory for CI)

**When to write feature tests:**
- Every API endpoint
- Every error code (SLOT_UNAVAILABLE, OTP_LOCKED, etc.)
- Every permission boundary
- Every status transition

**Format:**
```php
it('returns SLOT_UNAVAILABLE when slot is already reserved', function () {
    $user = User::factory()->player()->create();
    $venue = Venue::factory()->active()->create();
    
    SlotReservation::factory()->create([
        'venue_id' => $venue->id,
        'booking_date' => '2026-05-01',
        'start_time' => '10:00:00',
        'reserved_until' => now()->addMinutes(5),
    ]);
    
    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/payments/initiate', [
            'venue_id' => $venue->id,
            'booking_date' => '2026-05-01',
            'start_time' => '10:00:00',
            'duration_minutes' => 60,
            'provider' => 'wallet',
        ]);
    
    $response->assertStatus(422)
        ->assertJson(['error' => ['code' => 'SLOT_UNAVAILABLE']]);
});
```

---

### Unit Tests

Use for pure business logic with no HTTP or DB dependency.

**Always unit test:**
- `CommissionService::calculate()` — every edge case
- `SlotAvailabilityService::generate()` — timing edge cases, Syria calendar
- `TotpService::verify()` — time windows
- `WalletService::debit()` — balance checks
- `TierMatchingService::resolvePrice()` — day type resolution
- Phone normalization
- Booking code generation

**Never unit test:**
- Eloquent models (test via feature tests)
- Config reading
- Simple getters/setters

---

### Concurrency Tests

Critical for slot reservation and wallet debit. Use `Concurrency` facade (Laravel 13) or process forking.

**What to concurrency test:**
- Two simultaneous slot reservations for same slot → only one succeeds
- Two simultaneous wallet debits exceeding balance → only one succeeds
- Two simultaneous booking confirmations → only one booking created

**Format:**
```php
it('prevents double slot reservation under concurrent load', function () {
    $venue = Venue::factory()->active()->create();
    
    $results = collect(range(1, 10))->map(function () use ($venue) {
        return Process::run("php artisan test:reserve-slot {$venue->id} 2026-05-01 10:00");
    });
    
    $successes = $results->filter(fn($r) => str_contains($r->output(), 'success'))->count();
    
    expect($successes)->toBe(1);
    expect(SlotReservation::count())->toBe(1);
});
```

---

### Scheduler / Command Tests

Test scheduler commands with `artisan` helper. Use `Carbon::setTestNow()` to control time.

```php
it('marks confirmed past bookings as completed', function () {
    Carbon::setTestNow(now()->addHours(3));
    
    $booking = Booking::factory()->confirmed()->create([
        'ends_at' => now()->subHour(),
    ]);
    
    $this->artisan('bookings:complete')->assertExitCode(0);
    
    expect($booking->fresh()->status)->toBe('completed');
});
```

---

### Permission Coverage Test

One test that iterates all registered routes and asserts that each has a `permission:` middleware or is explicitly whitelisted as public.

```php
it('every non-public route has permission middleware', function () {
    $publicRoutes = [
        'api/v1/app/startup',
        'api/v1/auth/*',
        'api/v1/clubs',
        'api/v1/categories',
        // ... full public list
    ];
    
    $routes = collect(Route::getRoutes())->filter(fn($r) => 
        str_starts_with($r->uri(), 'api/admin') || str_starts_with($r->uri(), 'api/club')
    );
    
    foreach ($routes as $route) {
        $middlewares = $route->middleware();
        expect($middlewares)->toContain(fn($m) => str_starts_with($m, 'permission:'),
            "Route [{$route->uri()}] missing permission middleware"
        );
    }
});
```

---

## Test Tooling

| Tool | Purpose |
|------|---------|
| Pest PHP | Test runner (Laravel 13 default) |
| `RefreshDatabase` | Fresh DB per test |
| `WithFaker` | Test data generation |
| `Http::fake()` | Mock external HTTP (MTN, Syriatel, FCM, JWKS) |
| `Queue::fake()` | Assert jobs dispatched without running them |
| `Notification::fake()` | Assert notifications sent |
| `Carbon::setTestNow()` | Control time in scheduler tests |
| `Storage::fake()` | Media upload tests |
| Model factories | All models must have factories |

---

## What Must Be Mocked

| External Dependency | Mock Strategy |
|--------------------|--------------|
| MTN Cash API | `Http::fake()` with canned responses |
| Syriatel Cash API | `Http::fake()` |
| Fatora/SamaPay | `Http::fake()` for callback simulation |
| Google JWKS endpoint | `Http::fake()` with fixture JWT keys |
| Firebase FCM HTTP v1 | `Http::fake()` or `FcmService` mock |
| Baileys WhatsApp service | `Http::fake()` or service mock |
| football-data.org | `Http::fake()` with fixture response |

**Never** make real HTTP calls in tests.

---

## Test Database Strategy

- **CI:** SQLite in-memory (`:memory:`) — fast, isolated
- **Local dev:** MySQL test DB (`DB_DATABASE=daq_test`)
- `RefreshDatabase` trait on all feature tests
- Factories cover all models

---

## Coverage Expectations by Phase

| Phase | Minimum Coverage Target |
|-------|------------------------|
| Phase 2 (Auth) | 100% of auth flows, all error codes tested |
| Phase 3 (Domain) | All lifecycle transitions (club approval, etc.) |
| Phase 4 (Booking) | All slot edge cases, concurrency test passes |
| Phase 5 (Payments) | All provider flows, wallet race condition |
| Phase 6 (Dashboard) | Key workflows — not every UI state |
| Phase 7 (Hardening) | All above re-verified, no regression |

---

## Factories Required

Every model needs a factory. Minimum required states:

| Model | Required Factory States |
|-------|------------------------|
| User | `player()`, `superAdmin()`, `clubAdmin()`, `clubOwner()`, `clubDataEntry()` |
| Club | `active()`, `pendingApproval()`, `suspended()` |
| Venue | `active()`, `inactive()` |
| VenuePricingTier | `weekday()`, `weekend()`, `friday()` |
| Booking | `confirmed()`, `cancelled()`, `completed()` |
| Payment | `pending()`, `completed()`, `failed()` |
| SlotReservation | `active()`, `expired()` |
| Wallet | `withBalance($amount)` |

---

## Non-Negotiable Test Requirements

1. OTP code is never stored in plain text — verified by inspecting `otp_challenges.code_hash`
2. Concurrent slot reservation — exactly 1 succeeds in 10 parallel requests
3. Concurrent wallet debit — exactly 1 succeeds when both exceed balance
4. Commission: `total_price` always equals `venue_price` (no exceptions)
5. Cancellation within 30 min: zero DB changes on attempt
6. Club Admin cannot read another club's bookings (scoping test)
7. Settlement prevents double-counting (UNIQUE on settlement_items)
8. FCM not sent to users with `fcm_token = NULL`
