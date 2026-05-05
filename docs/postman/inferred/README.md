# Inferred Postman Collection

Sprint 1 generates `collection.json` here by scanning `routes/api.php` and
inferring the request shape for each endpoint. The Sprint 0 verification
script (`scripts/verify_endpoints.php`) consumes whatever JSON file lives at
`docs/postman/inferred/collection.json`; until Sprint 1 produces that file,
the script can be run against the existing handoff collection at
`docs/api-handoff/Daq-Ehjezly-API.postman_collection.json` for a smoke test.
