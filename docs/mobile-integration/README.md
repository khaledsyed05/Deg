# Mobile Integration

Artifacts for the 8-sprint Mobile Integration plan that aligns the Lamsa / دق احجزلي
Laravel backend with the mobile team's expectations.

The original source of truth was intended to be `BACKEND_REQUIREMENTS.md` at the
repo root. That file is **not present** in this checkout (see
[BLOCKERS.md](./BLOCKERS.md) — Sprint 0). The de-facto contract until it is
provided is `docs/api-handoff/mobile-integration-guide.md`.

## Sprints

- **Sprint 0 — Preparation:** branches, doc scaffold, test base class, schema
  snapshot, baseline test-suite audit, backlog clean-up.
- **Sprint 1 — Verification Pass:** run inferred Postman collection against the
  running app and record envelope / status mismatches.
- **Sprint 2 — Naming Alignment:** canonicalise `/api/v1/...` paths and resource
  field names.
- **Sprint 3 — Auth flows hardening:** OTP send / verify, Google sign-in,
  refresh-token semantics.
- **Sprint 4 — Booking flow end-to-end:** browse → availability → create →
  cancel.
- **Sprint 5 — Payments & wallet:** cash, Syriatel/MTN cash, bank-transfer,
  refunds.
- **Sprint 6 — Notifications & devices:** FCM tokens, notification preferences.
- **Sprint 7 — Football & content modules:** live scores, leagues, matches,
  followed teams, content pages.
- **Sprint 8 — Polish & handoff:** error-shape consistency, rate-limit headers,
  final Postman collection, mobile-team sign-off.

See [CHANGELOG.md](./CHANGELOG.md) for decisions and
[BLOCKERS.md](./BLOCKERS.md) for outstanding blockers.
