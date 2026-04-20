# Daq Ehjezly API — Mobile Integration Package

## Contents

| File | Description |
|------|-------------|
| `mobile-integration-guide.md` | Authentication flows, pagination, error handling, booking flow |
| `webhooks.md` | Payment webhook endpoints, signatures, retry policy |
| `Daq-Ehjezly-API.postman_collection.json` | Postman collection for all 41 endpoints |
| `openapi.json` | OpenAPI 3.0 specification (generate via server) |

## Quick Start

1. Import `Daq-Ehjezly-API.postman_collection.json` into Postman
2. Set environment variable `base_url` to your server URL
3. Run **Register** or **Verify OTP** — token is auto-set
4. Read `mobile-integration-guide.md` for complete integration guide

## Base URLs

| Environment | URL |
|-------------|-----|
| Local Dev | `http://localhost:8000/api` |
| Staging | `https://staging-api.daqehjezly.sy` |
| Docs (local) | `http://localhost:8000/api/documentation` |

## Auth Summary

All authenticated endpoints require:
```
Authorization: Bearer {token}
Accept: application/json
```

Token obtained via:
- `POST /v1/auth/register`
- `POST /v1/auth/otp/verify`
- `POST /v1/auth/google`

## API Groups

| Group | Prefix | Auth Required |
|-------|--------|---------------|
| Player API | `/v1/` | Most endpoints |
| Admin API | `/admin/v1/` | Yes (admin role) |
| Club Manager API | `/club/v1/` | Yes (manager role) |
| Payment Webhooks | `/webhooks/` | No (signature-verified) |

## Generate OpenAPI Spec

```bash
# Start local server
php artisan serve

# Fetch spec
curl http://localhost:8000/api/documentation.json > openapi.json
```

## Support

- Email: api@daqehjezly.sy
