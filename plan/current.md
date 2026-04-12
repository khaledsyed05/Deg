## Phase 1: Bootstrap and Repo Baseline

## Sprint 1: Delivery Baseline and Planning Control

**Goal:** Establish planning control and execution order before any app code is written.

---

## Sprint 1 Task Queue

| # | Task | Status |
|---|------|--------|
| 1 | Create `plan/current.md` as source of truth | done |
| 2 | Confirm project structure matches Laravel conventions | pending |
| 3 | Confirm `.env` and database connection are operational | pending |
| 4 | Confirm test suite runs clean with zero failures | pending |

---

## Tests Policy

- Every task that touches app code must include or update tests.
- Feature tests are preferred over unit tests.
- Tests must cover happy path, failure path, and key edge cases.
- No task is complete until its tests pass.

---

## Acceptance Criteria Style

- One task at a time.
- Acceptance criteria are written before execution begins.
- Criteria must be verifiable (test output, route response, or artisan command result).
- No task is signed off without criteria being met.

---

## Notes / Guardrails

- Do not start auth, schema, domain, booking, payment, or admin work in this sprint.
- Do not edit app code until sprint 2 tasks are defined and approved.
- Do not create additional docs or directories beyond what a task explicitly requires.
- Do not expand a task's scope during execution.
- One active task at a time. Complete and verify before moving to the next.
