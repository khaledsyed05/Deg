# Sprint 8 — Hardening Discovery

**Branch:** `feature/mobile-integration-sprint-8`
**Date:** 2026-05-06

---

## A1. Existing rate limits

- **`throttle:` middleware in `routes/api.php`:** **none.** Zero
  matches across the entire route file.
- **`RateLimiter::for(...)` registrations in providers:** **none.**
  No custom limiter definitions in `AppServiceProvider`,
  `AuthServiceProvider`, or `EventServiceProvider`.
- **Effective state:** every endpoint shipped through Sprints 0-7
  is unthrottled. The OTP-send / payment / message-send / pusher-
  auth surface is therefore wide open to abuse.

Sprint 8 B1 closes this completely — full matrix of 10 limiters
applied to every abuse-prone endpoint.

## A2. Existing audit infrastructure

- **`spatie/laravel-activitylog` package:** **present** (`^4.12`)
  in composer.json. Used by Spatie auto-logging on certain models;
  not relied on by the mobile-facing controllers.
- **Custom audit model:** **already exists** at
  `app/Models/AuditLog.php` with a `record()` static helper and
  the migration `2026_04_25_700001_create_audit_logs_table.php`.
- **Schema:** `id, user_id, action, subject_type, subject_id,
  changes (json), ip_address, timestamps`. Indexed on
  `(subject_type, subject_id)`, `user_id`, `action`.
- **Current usage:** wired into 9 admin-panel write operations
  (venue approve/reject/feature, user update/ban/unban, system
  maintenance, feature flag, broadcast). **NOT wired** into any
  mobile-facing wallet, team, auth, or booking operation.

### Decision: reuse existing AuditLog

Per the Sprint 8 prompt's anti-pattern ("do NOT create multiple
audit log models"), Sprint 8 wires the **mobile-facing** mutations
into the existing `AuditLog::record()` helper. The schema differs
slightly from the prompt's spec example (uses `subject_*` rather
than `auditable_*`, `changes` rather than `metadata`, no
`user_agent` column, no `(user_id, created_at)` composite index),
but the call shape is identical and the indexing is sufficient for
the read patterns we'll need.

If `(user_id, created_at)` becomes a hot lookup path later, that's
a follow-up migration. Not Sprint 8 scope.

## A3. Webhook endpoints

| Route | Controller | Current auth | Signature verification needed |
|---|---|---|---|
| POST /api/webhooks/mtn/callback | `Webhooks\MtnWebhookController@callback` | **none** (comment says "signature-verified" — aspirational) | YES |
| POST /api/webhooks/syriatel/callback | `Webhooks\SyriatelWebhookController@callback` | none | YES |
| POST /api/webhooks/fatora/callback | `Webhooks\FatoraWebhookController@callback` | none | YES (provider already uses HMAC-SHA256 in production) |
| POST /api/webhooks/bank/callback | (alias of fatora) | none | YES |
| POST /api/webhooks/bank-callback | `Webhooks\BankCallbackController@handle` | none | YES |
| POST /api/webhooks/samapay/callback | `Webhooks\SamaPayWebhookController@callback` | none | YES |

The route group is decorated with `Route::prefix('webhooks')` only —
no middleware. The "signature-verified" comment in `routes/api.php`
is aspirational; Sprint 8 makes it real.

### Coverage decision

The Sprint 8 prompt requires **at least one** webhook signature
verifier implemented end-to-end. Sprint 8 ships the primitive +
middleware as reusable infrastructure, plus applies it to the two
spec-mandated providers (Syriatel, MTN). Fatora/SamaPay/Bank
follow the same pattern but their secrets/headers vary by
provider — applying the middleware to those is a one-line change
once the providers' secrets land in config.

## A4. Pusher status (Sprint 7 BLOCKERS verbatim summary)

> 2026-05-06 — Sprint 7 — Pusher credentials missing from `.env`.
> The four `PUSHER_*` env vars were absent at the start of
> Sprint 7. Sprint 7 proceeded in degraded Mode B with placeholder
> test secrets. The live Debug Console smoke test was deferred.

**Current state (Sprint 8 audit):** still no `PUSHER_*` vars in
`.env`. Sprint 8 B4 will:

1. Re-check `.env` once.
2. If creds are now present, run the smoke test.
3. If still absent, document the failure mode crisply and update
   the BLOCKERS entry with a clear runbook for Khaled. Time-box at
   ≤2 hours per the prompt.

## Implementation order

1. **B1** — Rate limiting matrix + `AppServiceProvider` registrations
   + `throttle:` on routes + tests.
2. **B2** — Wire existing `AuditLog::record()` into wallet, team,
   booking, auth mutations + tests.
3. **B3** — `WebhookSignature` primitive + `VerifyWebhookSignature`
   middleware + apply to Syriatel/MTN webhooks + tests.
4. **B4** — Pusher live verification retry (time-boxed; document or
   resolve).
5. **B5** — Cleanup: deprecated geography aliases removal +
   auto-topup decision.
6. **B6** — Full suite green.

Then **Phase C** writes the final 4 docs plus the integration-plan
retrospective (the capstone deliverable).

## Out of scope

- Replacing the existing AuditLog with the prompt's slightly
  different schema (would force a migration for cosmetic gain).
- Audit-logging API access (those are infrastructure logs, not
  application concern — same as the prompt's anti-pattern says).
- Applying webhook signatures to Fatora/SamaPay/Bank — primitives
  + middleware are reusable; secrets/headers vary by provider and
  are a one-line follow-up per provider.
- Auto-topup execution — decision happens in B5 (likely deletion
  per the prompt's anti-pattern guidance).
