# Sprint 4 — Teams Discovery

**Branch:** `feature/mobile-integration-sprint-4`
**Date:** 2026-05-06

---

## A1. Team model audit

**Status:** Exists at `app/Models/Team.php` (created in Phase 17/18 work,
migration `2026_04_24_170001_create_teams_table.php`).

### Columns (current)

| Column | Type | Default | Notes |
|---|---|---|---|
| `id` | bigint | — | PK |
| `name` | string | — | |
| `description` | text | nullable | |
| `type` | enum(`casual`,`regular`,`competitive`) | `casual` | Cast to `App\Enums\TeamType` |
| `sport_category_id` | foreignId → `venue_categories.id` | — | RESTRICT on delete |
| `captain_id` | foreignId → `users.id` | — | CASCADE on delete |
| `max_members` | uint | 20 | |
| `is_public` | bool | false | |
| `requires_approval` | bool | false | |
| `regular_schedule` | json | nullable | |
| `avatar_url` | string | nullable | |
| `total_bookings` | uint | 0 | Counter |
| `total_members` | uint | 1 | Counter |
| `created_at` / `updated_at` | timestamps | — | |

### Casts

`is_public` (bool), `requires_approval` (bool), `regular_schedule` (array),
`type` (TeamType enum).

### Relationships

- `captain()` → `BelongsTo<User>` via `captain_id`
- `sportCategory()` → `BelongsTo<VenueCategory>` via `sport_category_id`
- `members()` → `HasMany<TeamMember>`
- `activeMembers()` → `members()` filtered by `status = 'active'`
- `bookings()` → `HasMany<Booking>`

### Helper methods

- `hasMember(int $userId): bool` — user has an active TeamMember row
- `isCaptain(int $userId): bool` — `captain_id === $userId`
- `canInvite(int $userId): bool` — user is captain or admin role
- `isFull(): bool` — active member count ≥ `max_members`
- `inviteMember(int $userId, int $invitedBy): TeamMember`
- Booted hook: when a Team is created, a TeamMember row is upserted for
  the captain with role=`captain`, status=`active`.

### Spec-mandated columns missing

None — the Team table already covers everything Sprint 4 needs. We will
add a `team_invites` table for invite codes (separate from the existing
`team_members.status='invited'` flow which uses direct user lookups).

---

## A2. Team membership audit

**Structure:** Dedicated `team_members` table (NOT a pivot — it has its
own model and lifecycle methods).

### Columns

| Column | Type | Default |
|---|---|---|
| `id` | bigint PK | |
| `team_id` | foreignId → teams CASCADE | |
| `user_id` | foreignId → users CASCADE | |
| `role` | enum(`captain`,`admin`,`member`) | `member` |
| `status` | enum(`invited`,`active`,`left`,`removed`) | `invited` |
| `invited_by` | foreignId → users nullable nullOnDelete | |
| `joined_at` | timestamp nullable | |
| `left_at` | timestamp nullable | |
| `created_at` / `updated_at` | timestamps | |

Unique index `(team_id, user_id)`. Index `(team_id, status)`, `user_id`.

### Captaincy encoding

**Two sources of truth in current code:**

1. `teams.captain_id` — the canonical captain pointer.
2. `team_members.role = 'captain'` — set by the `Team` booted hook when
   the team is created.

These can drift if not maintained. Sprint 4's transfer-captain
implementation MUST update both atomically.

---

## A3. Existing Teams endpoints audit

```
GET    /api/v1/teams              → TeamController@index   (sanctum)
POST   /api/v1/teams              → TeamController@store   (sanctum)
GET    /api/v1/teams/{id}         → TeamController@show    (sanctum)
PUT    /api/v1/teams/{id}/leave   → TeamController@leave   (sanctum)  [Phase 18]
```

| Route | Status (vs spec) | Auth | Notes |
|---|---|---|---|
| GET /teams | matches spec (lists user's teams) | sanctum | |
| POST /teams | matches spec (creates a team, auto-makes caller captain) | sanctum | Validation done inline in controller (TeamService caps at 5 teams/captain, blocks duplicate names) |
| GET /teams/{id} | matches spec | sanctum | Returns 403 if private and caller is not a member |
| PUT /teams/{id}/leave | exists; spec didn't surface it but it's present | sanctum | Uses `LeaveTeamService` + `TeamException` for 422/404 |

### Existing authorization logic

- **Inline checks** scattered in controller methods (e.g. `is_public &&
  ! hasMember()` → 403 in `show()`).
- **Service-level guards** in `LeaveTeamService` (only-admin can't leave,
  pending payments block, etc.) throwing `TeamException` with status code.
- **No Policy class** exists for Team yet. Sprint 4 introduces it.

### Test files

- `tests/Feature/Phase18/Teams/LeaveTeamTest.php` (3 tests).
- `tests/Feature/MobileEnvelope/Phase09TeamsSportsProfileTest.php`
  (5 envelope smoke tests including 3 for teams).
- **No factory:** `database/factories/` does not contain a `TeamFactory`
  or `TeamMemberFactory`. Existing tests use raw `Team::create([...])`
  with manually-inserted `venue_categories` rows. Sprint 4 will add
  proper factories.

---

## A4. Spec deep-read — Sprint 4 scope

Six new endpoints (per the prompt):

| # | Method | Path | Purpose |
|---|---|---|---|
| 1 | PUT | /teams/{id} | Update name / description / is_public / max_members |
| 2 | DELETE | /teams/{id} | Delete team (cascade members) |
| 3 | POST | /teams/{id}/kick | Captain removes a member |
| 4 | POST | /teams/{id}/transfer-captain | Hand captaincy to a member |
| 5 | POST | /teams/{id}/invite | Generate invite code (returns code + url) |
| 6 | GET | /teams/invite/{code} | Use the invite code to join the team |

### Decisions (spec is silent — captured here)

- **Invites use codes**, not direct user lookups. Joining a team via the
  existing `team_members.status='invited'` flow continues to work for
  apps that already use it; the new code-based invites are the mobile-
  facing path.
- **Kick / leave self:** the existing `PUT /teams/{id}/leave` covers
  self-removal. `POST /teams/{id}/kick` is captain-only (captain removes
  someone else). Captain cannot kick self → must transfer first.
- **Team deletion:** hard delete. The `team_members.team_id CASCADE` FK
  already removes membership rows on team deletion. No soft-delete
  column on the Team table, and adding one would change existing
  semantics, so we stay with hard delete.
- **Invite expiration:** opt-in (nullable `expires_at`). Default = no
  expiry. Mobile sends `{expires_at?, max_uses?}`.
- **Max active invites per team:** 5 (sensible default; rate-limits
  abuse). Active = not expired and not exhausted.
- **Use-invite when team is full:** rejected with `TeamFullException`
  (422), matching the existing `Team::isFull()` semantics.
- **Use-invite when already a member:** idempotent (200 with no change).

---

## A5. Authorization matrix

| Action | Captain | Member | Other authenticated | Admin (role) | Public (unauth) |
|---|---|---|---|---|---|
| View team (public) | ✅ | ✅ | ✅ | ✅ | ✅ |
| View team (private) | ✅ | ✅ | ❌ 403 | ✅ | ❌ 401 |
| Update team | ✅ | ❌ 403 | ❌ 403 | ✅ | ❌ 401 |
| Delete team | ✅ | ❌ 403 | ❌ 403 | ✅ | ❌ 401 |
| Kick a member | ✅ | ❌ 403 | ❌ 403 | ✅ | ❌ 401 |
| Transfer captain | ✅ (only current captain) | ❌ 403 | ❌ 403 | ❌ 403 | ❌ 401 |
| Generate invite code | ✅ | ❌ 403 | ❌ 403 | ✅ | ❌ 401 |
| Use invite code | ✅ idempotent | ✅ idempotent | ✅ joins | ✅ idempotent / joins | ❌ 401 |

### Notes on design choices

- **Transfer captain** is intentionally captain-only — even admins can't
  forcibly move ownership; that would be a moderation tool, not an
  end-user feature. If admins later need this, they can do it via the
  filament/db dashboard.
- **Use invite code** requires authentication (need to know who joined).
- **Public viewers** (unauthenticated) currently can't view teams at all
  because all team routes sit under `auth:sanctum`. We keep this; the
  `is_public` flag is reserved for "any *authenticated* user can view."

---

## A6. Migrations needed

1. Create `team_invites` table:
   - `id`, `team_id` (FK CASCADE), `code` (string, unique, indexed),
     `created_by` (FK → users CASCADE), `expires_at` (datetime nullable),
     `max_uses` (uint nullable, null = unlimited), `uses_count` (uint
     default 0), timestamps.
2. No migration needed against existing `teams` columns — schema covers
   the spec already.

### Factories needed

- `TeamFactory` with states `withCaptain(User)`, `public()`, `private()`,
  `withMembers(int $n)`.
- `TeamMemberFactory` with states `asMember()`, `asAdmin()`, `asCaptain()`.
- `TeamInviteFactory` with states `expired()`, `usedUp()`.

---

## Idempotency note

`App\Support\Idempotency::run()` is wallet-specific (returns
`WalletTransaction`). Team-mutating endpoints don't fit its signature.

For Sprint 4 we rely on:

- `team_members (team_id, user_id)` unique index — prevents duplicate
  joins via use-invite.
- `team_invites.code` unique index — prevents duplicate code generation.
- DB transactions in services that mutate multiple rows (transfer
  captain).

If a generic idempotency primitive is needed later, that's its own task.

---

## Out of scope for Sprint 4

- WhatsApp Business API integration for OTP delivery (Phase 0 only
  surfaces the `channel` parameter on `/auth/otp/send`; Baileys handles
  WhatsApp delivery already; SMS uses the existing Syriatel/MTN paths).
- Admin overrides for transfer-captain (see Authorization matrix note).
- Soft-delete on teams.
- Team avatar upload (separate `POST /teams/{id}/avatar` is its own
  feature; not in Sprint 4 scope).
