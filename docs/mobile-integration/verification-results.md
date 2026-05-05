# Mobile Integration — Verification Results

**Date:** 2026-05-05
**Test class root:** `tests/Feature/MobileEnvelope/`
**Run command:** `php artisan test tests/Feature/MobileEnvelope/`
**Spec source:** `BACKEND_REQUIREMENTS.md`

## Summary

| Outcome | Count | % |
|---|---|---|
| ✅ Match (test passes, envelope correct, status as expected) | 36 | 49% |
| ⚠️ Minor mismatch (envelope OK, data field divergence) | 0 | 0% |
| 🔴 Major mismatch (envelope wrong, or 4xx/5xx where 200 expected) | 37 | 51% |
| ⏸️ Skipped | 0 | 0% |
| **Total** | **73** | **100%** |

> The "0 ⚠️ minor" line reflects scope: Sprint 1 verification asserts the
> envelope shape and status code only. A follow-up sprint that probes
> per-resource data shape (using each endpoint's documented JSON example)
> will surface the minor / data-level mismatches; many of the current 🔴
> findings are likely to reclassify as ⚠️ once data-level checks land.

## Per-Phase Results

### Phase 1: Auth (12 endpoints)

| Method + Path | Outcome | Test | Finding |
|---|---|---|---|
| `POST /auth/otp/send` | ✅ | `Phase01AuthTest::test_post_auth_otp_send_returns_validation_envelope` | — |
| `POST /auth/otp/verify` | ✅ | `Phase01AuthTest::test_post_auth_otp_verify_returns_validation_envelope` | — |
| `POST /auth/otp/resend` | ✅ | `Phase01AuthTest::test_post_auth_otp_resend_returns_validation_envelope` | — |
| `POST /auth/register` | 🔴 | `Phase01AuthTest::test_post_auth_register_returns_documented_response` | Route missing — 404 (BLOCKERS.md unresolved) |
| `POST /auth/google` | ✅ | `Phase01AuthTest::test_post_auth_google_returns_validation_envelope` | — |
| `POST /auth/logout` | 🔴 | `Phase01AuthTest::test_post_auth_logout_returns_success_envelope` | HTTP 500 server error when authenticated |
| `GET /profile` | 🔴 | `Phase01AuthTest::test_get_profile_returns_success_envelope` | Envelope missing `message` key |
| `PUT /profile` | 🔴 | `Phase01AuthTest::test_put_profile_returns_success_envelope` | Envelope missing `message` key |
| `POST /profile/avatar` | ✅ | `Phase01AuthTest::test_post_profile_avatar_returns_envelope` | — |
| `DELETE /profile/avatar` | 🔴 | `Phase01AuthTest::test_delete_profile_avatar_returns_envelope` | Envelope missing `data` key |
| `POST /devices` | ✅ | `Phase01AuthTest::test_post_devices_returns_validation_envelope` | — |
| `POST /auth/refresh` | 🔴 | `Phase01AuthTest::test_post_auth_refresh_returns_envelope` | Envelope missing `data` key |

### Phase 2: Home + Discovery (10 endpoints)

| Method + Path | Outcome | Test | Finding |
|---|---|---|---|
| `GET /content/banners` | ✅ | `Phase02HomeTest::test_get_content_banners_returns_envelope` | — |
| `GET /content/featured` | ✅ | `Phase02HomeTest::test_get_content_featured_returns_envelope` | — |
| `GET /categories` | 🔴 | `Phase02HomeTest::test_get_categories_returns_envelope` | Returns raw `Resource::collection()` — no envelope |
| `GET /venues/featured` | 🔴 | `Phase02HomeTest::test_get_venues_featured_returns_envelope` | Returns raw `Resource::collection()` — no envelope |
| `GET /venues/popular` | ✅ | `Phase02HomeTest::test_get_venues_popular_returns_envelope` | — |
| `GET /venues/nearby` | 🔴 | `Phase02HomeTest::test_get_venues_nearby_returns_envelope` | HTTP 500 server error |
| `GET /venues/recently-viewed` | ✅ | `Phase02HomeTest::test_get_venues_recently_viewed_returns_envelope` | — |
| `GET /venues/search` | 🔴 | `Phase02HomeTest::test_get_venues_search_returns_envelope` | Returns raw `Resource::collection()` — no envelope |
| `GET /promotions/featured` | 🔴 | `Phase02HomeTest::test_get_promotions_featured_returns_envelope` | Returns raw `Resource::collection()` — no envelope |
| `GET /events` | ✅ | `Phase02HomeTest::test_get_events_returns_envelope` | — |

### Phase 3: Venue Detail + Booking (9 endpoints)

| Method + Path | Outcome | Test | Finding |
|---|---|---|---|
| `GET /venues/{slug}` | 🔴 | `Phase03BookingTest::test_get_venue_show_returns_envelope` | Envelope missing `message` key |
| `GET /venues/{slug}/availability` | ✅ | `Phase03BookingTest::test_get_venue_availability_returns_envelope` | — |
| `POST /bookings/check-availability` | ✅ | `Phase03BookingTest::test_post_bookings_check_availability_returns_envelope` | — |
| `POST /bookings/calculate-price` | ✅ | `Phase03BookingTest::test_post_bookings_calculate_price_returns_envelope` | — |
| `POST /bookings` | ✅ | `Phase03BookingTest::test_post_bookings_returns_envelope` | — |
| `GET /venues/{slug}/reviews` | 🔴 | `Phase03BookingTest::test_get_venue_reviews_returns_envelope` | Envelope missing `message` key |
| `POST /reviews` | ✅ | `Phase03BookingTest::test_post_reviews_returns_envelope` | — |
| `PUT /bookings/{id}/cancel` | 🔴 | `Phase03BookingTest::test_put_booking_cancel_returns_envelope` | Envelope missing `data` key |
| `PUT /bookings/{id}/reschedule` | 🔴 | `Phase03BookingTest::test_put_booking_reschedule_returns_envelope` | Envelope missing `data` key |

### Sprint: Maps + Filters + Settings (4 endpoints)

| Method + Path | Outcome | Test | Finding |
|---|---|---|---|
| `GET /cities` (popular) | ✅ | `PhaseSprintMapsSettingsTest::test_get_cities_returns_envelope` | — |
| `GET /cities/{id}/neighborhoods` | 🔴 | `PhaseSprintMapsSettingsTest::test_get_city_neighborhoods_returns_envelope` | HTTP 422 — endpoint expects different params or doesn't exist as named |
| `GET /venues/clusters` | 🔴 | `PhaseSprintMapsSettingsTest::test_get_venues_clusters_returns_envelope` | Envelope missing `data` key |
| `GET /venues/by-bounds` | 🔴 | `PhaseSprintMapsSettingsTest::test_get_venues_by_bounds_returns_envelope` | Envelope missing `data` key |

### Phase Matches / Waitlist / Deals (14 endpoints — 3 waitlist gaps deferred)

| Method + Path | Outcome | Test | Finding |
|---|---|---|---|
| `GET /football/matches/today` | ✅ | `PhaseMatchesWaitlistDealsTest::test_get_football_matches_today_returns_envelope` | — |
| `GET /football/matches/upcoming` | ✅ | `…upcoming_returns_envelope` | — |
| `GET /football/matches/yesterday` | ✅ | `…yesterday_returns_envelope` | — |
| `GET /football/matches/{slug}` | 🔴 | `…match_show_returns_envelope` | Envelope missing `data` key |
| `GET /football/live/matches` | ✅ | `…live_matches_returns_envelope` | — |
| `GET /football/live/matches/{id}/events` | ✅ | `…live_match_events_returns_envelope` | — |
| `GET /football/live/matches/{id}/lineups` | ✅ | `…live_match_lineups_returns_envelope` | — |
| `GET /football/live/matches/{id}/statistics` | ✅ | `…live_match_statistics_returns_envelope` | — |
| `GET /football/leagues` | ✅ | `…leagues_returns_envelope` | — |
| `GET /football/leagues/{id}/standings` | ✅ | `…league_standings_returns_envelope` | — |
| `GET /waitlist` | 🔴 | `…get_waitlist_returns_envelope` | Envelope missing `message` key |
| `POST /waitlist` | ✅ | `…post_waitlist_returns_envelope` | — |
| `DELETE /waitlist/{id}` | 🔴 | `…delete_waitlist_returns_envelope` | Envelope missing `data` key |
| `GET /promotions` | 🔴 | `…get_promotions_returns_envelope` | Envelope missing `success` key |

### Phase 7: Tournaments + Notifications (10 endpoints — 1 FCM gap deferred)

| Method + Path | Outcome | Test | Finding |
|---|---|---|---|
| `GET /events` | ✅ | `Phase07TournamentsNotificationsTest::test_get_events_returns_envelope` | — |
| `GET /events/{id}` | 🔴 | `…event_show_returns_envelope` | Envelope missing `data` key |
| `POST /events/{id}/register` | 🔴 | `…event_register_returns_envelope` | Envelope missing `data` key |
| `GET /events/registered` | ✅ | `…events_registered_returns_envelope` | — |
| `DELETE /events/{id}/registration` | 🔴 | `…event_registration_returns_envelope` | Envelope missing `data` key |
| `GET /notifications` | 🔴 | `…get_notifications_returns_envelope` | Envelope missing `success` key |
| `GET /notifications/unread` | ✅ | `…notifications_unread_returns_envelope` | — |
| `PUT /notifications/{id}/read` | 🔴 | `…notification_read_returns_envelope` | Envelope missing `data` key |
| `PUT /notifications/read-all` | ✅ | `…notifications_read_all_returns_envelope` | — |
| `DELETE /notifications/{id}` | 🔴 | `…delete_notification_returns_envelope` | Envelope missing `data` key |

### Phase 8: Wallet + Coupons (5 covered endpoints)

| Method + Path | Outcome | Test | Finding |
|---|---|---|---|
| `GET /wallet/account` | 🔴 | `Phase08WalletCouponsTest::test_get_wallet_account_returns_envelope` | Envelope missing `data` key |
| `GET /wallet/transactions` | 🔴 | `…wallet_transactions_returns_envelope` | Envelope missing `success` key |
| `GET /wallet/settings` | 🔴 | `…get_wallet_settings_returns_envelope` | Envelope missing `data` key |
| `PUT /wallet/settings` | 🔴 | `…put_wallet_settings_returns_envelope` | Envelope missing `data` key |
| `POST /wallet/topup` | ✅ | `…post_wallet_topup_returns_envelope` | — |

### Phase 9: Teams + Sports Profile (5 covered endpoints)

| Method + Path | Outcome | Test | Finding |
|---|---|---|---|
| `GET /teams` | ✅ | `Phase09TeamsSportsProfileTest::test_get_teams_returns_envelope` | — |
| `GET /teams/{id}` | 🔴 | `…team_show_returns_envelope` | Envelope missing `data` key |
| `POST /teams` | ✅ | `…post_teams_returns_envelope` | — |
| `GET /profile/stats` | ✅ | `…profile_stats_returns_envelope` | — |
| `GET /profile/achievements` | ✅ | `…profile_achievements_returns_envelope` | — |

### Phase 10: Chat (4 generic Social endpoints — chat suite deferred to Sprint 7)

| Method + Path | Outcome | Test | Finding |
|---|---|---|---|
| `GET /conversations` | 🔴 | `Phase10ChatTest::test_get_conversations_returns_envelope` | Envelope missing `data` key |
| `GET /conversations/{id}` | 🔴 | `…conversation_show_returns_envelope` | Envelope missing `data` key |
| `GET /conversations/{id}/messages` | 🔴 | `…conversation_messages_returns_envelope` | Envelope missing `data` key |
| `GET /chat/unread-summary` | 🔴 | `…chat_unread_summary_returns_envelope` | Envelope missing `data` key |

## Top Findings (rolled up)

1. **Many controllers omit the `message` field on success.** ~5 endpoints
   under `/profile`, `/venues/{slug}`, `/venues/{slug}/reviews`, etc., return
   `{success: true, data: ...}` only. The spec contract requires
   `message: string|null` always present. Single fix — adjust those
   controllers to use the existing `App\Http\Traits\ApiResponse::success()`
   helper, which already includes `message`.

2. **Pagination & resource collections bypass the envelope entirely.**
   ~6 endpoints (`/categories`, `/venues/featured`, `/venues/search`,
   `/promotions/featured`, `/promotions`, `/wallet/transactions`,
   `/notifications`) return Laravel's default
   `JsonResource::collection($paginator)` shape, which is
   `{data, links, meta}` — no `success` key. Single global fix —
   override `JsonResource::wrap('data')` plus a response macro that
   wraps every paginated collection.

3. **Endpoints that produce a "no-content" success skip the `data` key.**
   ~18 endpoints (delete-style, mark-as-read, registration, wallet
   reads, conversation reads, etc.) return `{success: true,
   message: ...}` without `data: null`. Per spec the `data` key must
   exist (use `null` if no payload). Sprint 2 fix.

4. **Two real 500s.** `POST /auth/logout` and `GET /venues/nearby`
   throw uncaught exceptions when called from the verification tests
   (logout while authenticated; nearby with valid lat/lng). These are
   bugs, not envelope mismatches.

5. **Geography routes diverge in path naming.** The spec writes
   `GET /cities`, `GET /cities/{id}/neighborhoods`, `GET /venues/clusters`,
   `GET /venues/by-bounds`. The codebase exposes
   `/api/v1/geography/cities/popular`, `/api/v1/geography/cities/{id}`,
   `/api/v1/geography/venues/clusters`, with no
   `/venues/by-bounds` route at all. Path-naming alignment is the
   focus of Sprint 2; flagged here.
