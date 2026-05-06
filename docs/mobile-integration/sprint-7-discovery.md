# Sprint 7 — Chat + Pusher Discovery

**Branch:** `feature/mobile-integration-sprint-7`
**Date:** 2026-05-06

---

## Mode

**Mode B (Full Pusher) — running in DEGRADED state.** See
`BLOCKERS.md` entry `2026-05-06 — Sprint 7`. Real `PUSHER_*`
credentials are absent from `.env`. All code paths build and test;
the live Debug Console smoke test (B6) is deferred to a Khaled-
driven verification once creds land. Test suite uses placeholder
secrets in `phpunit.xml` so HMAC signing math is exercised.

## A1. Existing chat code

- **Tables:** none. SQLite/MySQL audit returned no `conversations`,
  `messages`, `message_reads`, or `conversation_participants`.
- **Models:** only `App\Models\TicketMessage` exists, and that's
  the support-ticket subsystem — unrelated to chat.
- **Routes:** `route:list | grep -iE "chat|conversation|message|
  pusher"` returned nothing under `api/v1/`.
- **Tests:** `tests/Feature/MobileEnvelope/Phase10ChatTest.php` has
  4 envelope smoke tests for the read endpoints (currently they
  exercise the canonical 404 envelope because no routes are
  registered — Sprint 7 flips them to 200 + envelope).

Sprint 7 builds chat from scratch.

## A2. Spec endpoint inventory

9 endpoints total. All require `auth:sanctum`.

| # | Method | Path | Notes |
|---|---|---|---|
| 1 | GET | `/conversations` | Paginated list of caller's conversations |
| 2 | GET | `/conversations/{id}` | Single conversation detail |
| 3 | GET | `/conversations/{id}/messages` | Cursor-paginated; `?before_id=`, `?limit=50` (max 100) |
| 4 | GET | `/chat/unread-summary` | `{total_unread, by_channel: [...]}` |
| 5 | POST | `/messages` | Send. Body: `{conversation_id, body, idempotency_key}` |
| 6 | POST | `/messages/{id}/mark-read` | Idempotent |
| 7 | POST | `/pusher/auth` | **Security-critical.** `{socket_id, channel_name}` |
| 8 | POST | `/conversations/{id}/mute` | Toggle mute; idempotent |
| 9 | POST | `/conversations/{id}/leave` | Sets `left_at`; conversation history preserved |

### Path-parameter naming

Spec uses `channelId` in some path examples; this codebase uses `id`
(matching the existing `Phase10ChatTest.php` URLs and every other
controller in `app/Http/Controllers/Api/V1/`). We register routes
with `{id}` (numeric) to match.

### Channel naming convention (from spec line 4687-4689)

- DM: `private-dm-{u1}-{u2}` where `u1 < u2` (sorted ascending)
- Team: `private-team-{team_id}`
- Group booking: `private-group-{booking_id}`

### Event names (from spec line 4691-4695)

- `message.created` — new message
- `message.read` — read receipt
- `member.joined` — joined a group/team chat
- `member.left` — left

(Typing indicators are flagged "optional" in the spec — **out of
scope** this sprint.)

### Attachments

Spec mentions message types `text|image|booking|system` and an
`attachments[]` array. Sprint 7 stores `type` and `attachment_url`
(string nullable) as **metadata only**. No upload pipeline; mobile
sends an already-hosted URL or the field is null. Real upload
plumbing would be Sprint 8 territory.

### Pagination for messages

Cursor-based via `before_id`. Default 50, max 100. Returned
**oldest-to-newest within the page** (chat UIs prepend older messages
on scroll-up).

### Read-receipt semantics

Per-user `read_at`. No "delivered" state — Pusher's transport
already implies delivery; the explicit "read" timestamp is the
only state we track. Best-effort, NOT transactional.

## A3. Channel auth matrix

| Channel pattern | Read access | Send access |
|---|---|---|
| `private-dm-{u1}-{u2}` | exactly `u1` and `u2` (after numeric sort) | same |
| `private-team-{team_id}` | active `team_members` rows | same |
| `private-group-{booking_id}` | rows in `booking_participants` (or booking owner) | same |
| anything else | 403 | 403 |

Admins do **NOT** get blanket read access to chat content. Privacy
is the contract.

## A4. Schema plan

### `conversations`

| Column | Type | Notes |
|---|---|---|
| id | bigInt PK | |
| type | enum(`dm`, `team`, `group`) | |
| reference_id | bigInt nullable | `team_id` for `team`, `booking_id` for `group`, NULL for `dm` |
| created_at | timestamp | |
| updated_at | timestamp | bumped when a message is sent (drives the list-sort) |
| deleted_at | timestamp nullable | soft delete |

Indexes: `(type, reference_id)` for "find existing team/group
conversation"; `(updated_at)` is implicit via the index Laravel
adds.

### `conversation_participants`

| Column | Type | Notes |
|---|---|---|
| id | bigInt PK | |
| conversation_id | FK → conversations CASCADE | |
| user_id | FK → users CASCADE | |
| joined_at | timestamp | |
| left_at | timestamp nullable | |
| muted_at | timestamp nullable | |
| timestamps | | |

Composite unique `(conversation_id, user_id)`. Index `(user_id,
conversation_id)` for "list my conversations".

### `messages`

| Column | Type | Notes |
|---|---|---|
| id | bigInt PK | |
| conversation_id | FK CASCADE | |
| user_id | FK → users (sender) | nullOnDelete (preserve "user X said" even after account delete) |
| body | text nullable | |
| type | enum(`text`, `image`, `audio`, `booking`, `system`) default `text` | |
| attachment_url | string(500) nullable | |
| created_at | timestamp | |
| deleted_at | timestamp nullable | soft delete |

Indexes: `(conversation_id, created_at DESC)` — primary access
pattern, mandatory; `(deleted_at)` — to keep soft-delete-aware
counts cheap.

### `message_reads`

| Column | Type | Notes |
|---|---|---|
| id | bigInt PK | |
| message_id | FK CASCADE | |
| user_id | FK → users CASCADE | |
| read_at | timestamp | |
| timestamps | | |

Composite unique `(message_id, user_id)`. Index `(user_id, read_at)`
for unread-count queries.

## A5. Event classes plan

All four extend Laravel's broadcasting infrastructure with
`ShouldBroadcast` (queued — not `ShouldBroadcastNow`). All return
`PrivateChannel` instances per the spec convention.

| Event | When | Channel | Payload |
|---|---|---|---|
| `MessageCreated` | `POST /messages` succeeds | The conversation's private channel | full message resource |
| `MessageRead` | `POST /messages/{id}/mark-read` | same | `{message_id, user_id, read_at}` |
| `MemberJoined` | new participant added | same | `{user_id}` |
| `MemberLeft` | `POST /conversations/{id}/leave` | same | `{user_id}` |

`broadcastAs()` returns the dotted spec name (`message.created`,
etc.) so the mobile listener strings match.

### How tests verify dispatch

`Event::fake([MessageCreated::class, ...])` at the top of the
mutating tests, then `Event::assertDispatched(MessageCreated::class,
function ($e) {...})` in the body. Real Pusher is never hit in CI.

## A6. Channel name resolution

Each conversation stores `type` + `reference_id` and produces its
Pusher channel name via a method:

```php
// On Conversation model:
public function channelName(): string
{
    return match ($this->type) {
        'team'  => "private-team-{$this->reference_id}",
        'group' => "private-group-{$this->reference_id}",
        'dm'    => $this->dmChannelName(),
    };
}
```

For DMs the channel is `private-dm-{u1}-{u2}` with the two user IDs
sorted ascending — derived from the two `conversation_participants`
rows.

## Implementation order

1. **B1** — Migrations + models + factories
2. **B2** — Read endpoints (4): list, show, messages, unread-summary
3. **B3** — Mutating endpoints (2): send message (with Idempotency),
   mark-read
4. **B4** — `/pusher/auth` with comprehensive channel-authorization
   tests (the security-critical work; ≥12 tests)
5. **B5** — Mute + leave endpoints
6. **B6** — Broadcasting verification (event classes wired,
   dispatch asserted via `Event::fake()`; live Debug Console smoke
   test deferred per BLOCKERS)
7. **B7** — MobileEnvelope coverage update
8. **B8** — Full suite green

## Idempotency note

`POST /messages` uses `App\Support\Idempotency::run()` keyed by the
client-supplied `idempotency_key`. The primitive's wallet-typed
return is a known constraint — Sprint 7 chooses to either
(a) refactor the primitive to be generic, or (b) build a
chat-specific equivalent. **Decision: (b) — `MessageIdempotency`
helper in `app/Support/`.** The wallet primitive's signature
returns `WalletTransaction`; refactoring it would touch the wallet
codebase and is out of scope. We re-implement the same lock + key
pattern in 30 lines targeting `Message`.

`mark-read` does NOT need an idempotency primitive — the
`(message_id, user_id)` unique index makes duplicate inserts a
no-op via `firstOrCreate`.

## Out of scope

- Typing indicators (spec marks optional)
- Message editing (spec doesn't mandate; messages are append-only
  with soft-delete)
- Message reactions (not in spec)
- Real upload pipeline for attachments (Sprint 8)
- Live Pusher Debug Console smoke test (deferred — see BLOCKERS)
- Admin override on chat content (privacy contract)
