# Mobile Integration — CHANGELOG

Running log of decisions taken during the 8-sprint Mobile Integration plan.

Format:

```
## YYYY-MM-DD — Sprint N — <title>
**Decision:** <what was decided>
**Rationale:** <why>
**Files affected:** <list>
```

---

## 2026-05-05 — Sprint 0 — Default branch resolution

**Decision:** Branch `feature/mobile-integration` is cut from `master`, not
`main`.
**Rationale:** Although the local repository has both `main` and `master`,
`origin/main` was deleted on the remote and `master` is the only branch that
exists on `origin`. `master` is also the active development branch (HEAD of
local work prior to this sprint). Branching from anywhere else would lose
in-flight commits.
**Files affected:** none (branch metadata only).

---

## 2026-05-05 — Sprint 0 — Pre-requisite document missing

**Decision:** Proceed with Sprint 0 using
`docs/api-handoff/mobile-integration-guide.md` as the de-facto contract for the
response envelope, role list, and Postman variables. Logged separately in
`BLOCKERS.md`.
**Rationale:** Khaled re-issued the Sprint 0 prompt twice after I flagged that
`BACKEND_REQUIREMENTS.md` is absent from the repo root. Per the Cardinal Rule
("do not consult; pick, document, proceed") the only consistent reading of the
re-issuance is "proceed without it". The handoff guide already documents the
envelope (`{success, data, meta?}` for success, `{success: false, message,
errors}` for errors) and matches the format the prompt's helpers describe, so
inference is low-risk.
**Files affected:** `tests/MobileIntegrationTest.php`,
`tests/Feature/MobileIntegrationBaseTest.php`,
`docs/mobile-integration/postman/local.postman_environment.json`.

---

## 2026-05-05 — Sprint 0 — Auth driver for `actingAsRole`

**Decision:** `actingAsRole` uses Sanctum (`actingAs($user, 'sanctum')`),
matching the existing `Tests\TestCase::actingAsPlayer/Admin/ClubManager`
helpers.
**Rationale:** `composer.json` declares `laravel/sanctum`; no JWT package is
installed. The existing test helpers all act-as via the `sanctum` guard.
**Files affected:** `tests/MobileIntegrationTest.php`.

---

## 2026-05-05 — Sprint 0 — Roles seeded by base TestCase

**Decision:** `actingAsRole` accepts the four roles named in the prompt
(`player`, `club_manager`, `club_staff`, `admin`) and relies on the existing
`RolesAndPermissionsSeeder` already invoked in `Tests\TestCase::setUp()`.
**Rationale:** Spatie Permission is installed, the seeder runs before every
test, and the existing helpers already rely on these role names.
**Files affected:** `tests/MobileIntegrationTest.php`.
