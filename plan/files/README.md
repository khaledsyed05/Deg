# Daq Ehjezly — Laravel 13 Delivery Plan

## What This Is

This is the backend-first execution plan for the Daq Ehjezly sports venue booking platform. It is derived directly from `implementation_spec.md`, `database_schema.md`, `final_locked_decisions.md`, and `phase1_lock.md`.

This plan covers **Phase 1 scope only**. Nothing in this plan is speculative. Every task maps to a confirmed decision in the spec documents.

## Source of Truth Hierarchy

```
final_locked_decisions.md   ← wins all conflicts
implementation_spec.md      ← execution reference
database_schema.md          ← schema reference
phase1_lock.md              ← scope gate
```

## Plan Files

| File | Purpose |
|------|---------|
| `README.md` | This file — orientation |
| `phases_overview.md` | 7 phases, their goals, scope boundaries, sequencing logic |
| `sprint_plan.md` | Every sprint: objective, tasks list, risks, exit criteria |
| `task_breakdown.md` | Every task: ID, goal, scope, deps, files, tests, AC, DoD |
| `test_strategy.md` | Testing philosophy, what to test, how, tooling |
| `acceptance_criteria.md` | Consolidated, checkable AC per feature area |

## Technology Stack (Locked)

| Concern | Decision |
|---------|---------|
| Framework | Laravel 13 |
| Database | MySQL 8.0+ / utf8mb4 / InnoDB |
| Queue | Database queue (no Redis) |
| Cache | File cache (no Redis) |
| Auth (players) | Laravel Sanctum — `api` guard |
| Auth (dashboard) | Session — `web` guard |
| 2FA | Custom `TotpService` — PHP native, RFC 6238 |
| Google auth | Custom `FirebaseAuthService` via JWKS — no kreait |
| FCM | Firebase HTTP v1 — no package |
| Permissions | `spatie/laravel-permission` |
| Media | `spatie/laravel-medialibrary` |
| Audit | `spatie/laravel-activitylog` |
| Settings | `spatie/laravel-settings` |
| Opening hours | `spatie/opening-hours` |
| Translations | `spatie/laravel-translatable` |
| Dashboard UI | Inertia.js + Vue 3 + Tailwind CSS 4 + shadcn-vue |
| Storage | Local (`storage/app/public`) |
| Geography data | `dr5hn/countries-states-cities-database` (ODbL) — seeded once |

## Key Constraints This Plan Respects

- Commission is always **deducted from club**. `apply_as` does not exist.
- Deposit = electronic partial payment. Remaining = cash at venue (outside platform).
- Recurring bookings are Phase 2. Schema fields exist but no mobile API.
- OTP cancellation phrase is removed. `{confirmed: true}` only.
- WhatsApp auto-detects. No choice screen.
- `venue_categories` is the venue category system. `sport_categories` is for Events tab only.
- Slots are computed on-the-fly. No `slots` table.
- No-show is set manually by Club Admin. No automated job.

## Sequencing Rationale

```
Phase 1: Bootstrap        → project compiles, migrates, seeds
Phase 2: Auth & Roles     → every subsequent task depends on working auth
Phase 3: Core Domain      → geography, catalog, clubs, venues
Phase 4: Booking Engine   → availability, slot reservation, booking lifecycle
Phase 5: Payments         → all providers, wallet, deposit, commission, settlement
Phase 6: Admin & Dash     → both dashboards, observability, notifications, events
Phase 7: Hardening        → coverage, performance, launch readiness
```

No phase starts until its predecessor's exit criteria are met.
