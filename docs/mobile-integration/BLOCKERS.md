# Mobile Integration — BLOCKERS

Add an entry only if a step is genuinely impossible to complete without
external action.

Format:

```
## YYYY-MM-DD — Sprint N — <title>
**Blocker:** <what's blocked>
**Why:** <root cause>
**Needs:** <what action from Khaled / external party>
**Workaround applied:** <if any>
```

---

## 2026-05-05 — Sprint 0 — `BACKEND_REQUIREMENTS.md` not present

**Blocker:** The Sprint 0 pre-requisite document is missing from the repo
root.
**Why:** The file was never committed to this repository. A search across
`/var/www/deg-ehjezli` and `~` for any case-insensitive match of
`BACKEND_REQUIREMENTS*` returned no results. The closest existing artefacts
are `docs/api-handoff/mobile-integration-guide.md`, `docs/api-handoff/README.md`,
and `docs/api-handoff/Daq-Ehjezly-API.postman_collection.json`.
**Needs:** Khaled to either (a) drop `BACKEND_REQUIREMENTS.md` at the repo
root, or (b) confirm that `docs/api-handoff/mobile-integration-guide.md` is
the canonical contract (in which case this entry can be retired).
**Workaround applied:** Sprint 0 proceeded using
`docs/api-handoff/mobile-integration-guide.md` as the contract for the
response envelope, role list, and Postman variables. See CHANGELOG entry for
2026-05-05.
