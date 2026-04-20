# Mobile Integration Guide — دق احجزلي API

## Base URLs

| Environment | URL |
|-------------|-----|
| Local Dev | `http://localhost:8000/api` |
| Staging | `https://staging-api.daqehjezly.sy` |
| Production | TBD |

---

## Authentication

### Flow 1: Phone + OTP Login

**Step 1 — Send OTP**

```http
POST /v1/auth/otp/send
Content-Type: application/json

{
  "phone": "+963944123456"
}
```

Response:
```json
{
  "success": true,
  "data": {
    "challenge_uuid": "550e8400-e29b-41d4-a716-446655440000",
    "expires_in_seconds": 120
  }
}
```

**Step 2 — Verify OTP**

```http
POST /v1/auth/otp/verify
Content-Type: application/json

{
  "phone": "+963944123456",
  "challenge_uuid": "550e8400-e29b-41d4-a716-446655440000",
  "otp": "123456",
  "fcm_token": "firebase-device-token"
}
```

Response:
```json
{
  "success": true,
  "data": {
    "token": "1|abcdef123456...",
    "user": {
      "id": 1,
      "name": "محمد الأحمد",
      "phone_number": "+963944123456"
    }
  }
}
```

### Flow 2: Registration

```http
POST /v1/auth/register
Content-Type: application/json

{
  "name": "محمد الأحمد",
  "phone_number": "+963944123456",
  "password": "password123",
  "password_confirmation": "password123"
}
```

### Flow 3: Google Sign-In

```http
POST /v1/auth/google
Content-Type: application/json

{
  "id_token": "firebase-google-id-token",
  "fcm_token": "firebase-device-token"
}
```

### Using the Token

Include in all authenticated requests:

```http
GET /v1/bookings
Authorization: Bearer 1|abcdef123456...
Accept: application/json
```

### Logout

```http
POST /v1/auth/logout
Authorization: Bearer {token}
```

---

## Pagination

All list endpoints use standard Laravel pagination:

```http
GET /v1/venues?per_page=20&page=2
```

Response:
```json
{
  "success": true,
  "data": [...],
  "meta": {
    "current_page": 2,
    "last_page": 5,
    "per_page": 20,
    "total": 92
  }
}
```

---

## Error Handling

### Standard Error Response

```json
{
  "success": false,
  "message": "رسالة الخطأ",
  "errors": {
    "phone_number": ["رقم الهاتف مطلوب"]
  }
}
```

### HTTP Status Codes

| Code | Meaning | Action |
|------|---------|--------|
| 200 | Success | — |
| 201 | Created | — |
| 401 | Unauthorized | Redirect to login |
| 403 | Forbidden | Show access denied |
| 404 | Not Found | Show not found |
| 422 | Validation Failed | Show field errors |
| 429 | Rate Limited | Retry after delay |
| 500 | Server Error | Retry with backoff |
| 503 | Service Unavailable | Show maintenance screen |

---

## Rate Limiting

- **Global:** 60 requests/minute per IP
- **Authenticated:** 120 requests/minute per user
- **OTP Send:** 3 requests/10 minutes per phone number

**Rate limit headers:**
```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 45
X-RateLimit-Reset: 1619028600
```

**Rate limited response (429):**
```json
{
  "success": false,
  "message": "عدد الطلبات كثير جداً، يرجى المحاولة بعد قليل",
  "retry_after": 42
}
```

---

## Booking Flow

### 1. Browse Venues

```http
GET /v1/venues?city_id=1&category_id=2&per_page=15
```

### 2. Search Venues

```http
GET /v1/venues/search?query=ملعب&city_id=1
```

### 3. Check Available Slots

```http
GET /v1/venues/1/slots?date=2026-04-25&duration_minutes=60
```

Response:
```json
{
  "success": true,
  "data": {
    "available": true,
    "unavailable_reason": null
  }
}
```

### 4. Create Booking

```http
POST /v1/bookings
Authorization: Bearer {token}
Content-Type: application/json

{
  "venue_id": 1,
  "booking_date": "2026-04-25",
  "start_time": "18:00",
  "duration_minutes": 60,
  "payment_mode": "deposit",
  "payment_provider": "mtn_cash",
  "notes": "أرجو تجهيز الملعب"
}
```

Response:
```json
{
  "success": true,
  "data": {
    "id": 123,
    "booking_code": "BK12345",
    "status": "pending_payment",
    "total_price": 50000,
    "deposit_amount": 15000,
    "remaining_amount": 35000
  },
  "commission": {
    "total_price": 50000,
    "commission_amount": 3500,
    "club_payout": 46500
  }
}
```

### 5. Initiate Payment

```http
POST /v1/payments/initiate
Authorization: Bearer {token}
Content-Type: application/json

{
  "booking_id": 123,
  "payment_provider": "mtn_cash",
  "phone_number": "+963933123456"
}
```

Response (OTP flow):
```json
{
  "success": true,
  "data": {
    "id": 456,
    "status": "pending"
  },
  "flow_type": "otp",
  "webview_url": null
}
```

Response (Webview flow — Fatora):
```json
{
  "success": true,
  "data": {
    "id": 457,
    "status": "pending"
  },
  "flow_type": "webview",
  "webview_url": "https://pay.fatora.io/invoice/..."
}
```

### 6. Confirm OTP Payment

```http
POST /v1/payments/mtn/confirm
Authorization: Bearer {token}
Content-Type: application/json

{
  "payment_id": 456,
  "otp": "654321"
}
```

---

## Payment Providers

| Provider | Value | Flow | Notes |
|----------|-------|------|-------|
| MTN Cash | `mtn_cash` | OTP | Requires `phone_number` starting with +963933 |
| Syriatel Cash | `syriatel_cash` | OTP | Requires `phone_number` starting with +963944 |
| Fatora | `fatora` | Webview | Opens browser payment page |
| Wallet | `wallet` | Internal | Requires sufficient balance |

---

## Waitlist

Join a venue's waitlist for a specific slot:

```http
POST /v1/waitlist
Authorization: Bearer {token}
Content-Type: application/json

{
  "venue_id": 5,
  "preferred_date": "2026-04-25",
  "preferred_time": "18:00",
  "duration_minutes": 60
}
```

List my waitlist entries:
```http
GET /v1/waitlist
Authorization: Bearer {token}
```

Leave waitlist:
```http
DELETE /v1/waitlist/1
Authorization: Bearer {token}
```

---

## Best Practices

### 1. Always Include Accept Header
```
Accept: application/json
```
This ensures JSON error responses instead of HTML redirects.

### 2. Always Send FCM Token on Login
Update on every login:
```json
{ "fcm_token": "latest-firebase-device-token" }
```

### 3. Handle Arabic Text
- All user-facing messages are in Arabic
- Support RTL layout in the app
- Use UTF-8 encoding for all requests

### 4. Retry on Server Errors
Retry 5xx responses with exponential backoff:
- 1st retry: 1 second
- 2nd retry: 2 seconds
- 3rd retry: 4 seconds
- Max: 3 retries

### 5. Cache Public Data
| Endpoint | Cache Duration |
|----------|---------------|
| `/v1/venues` | 5 minutes |
| `/v1/venues/{id}` | 1 minute |
| City list | 1 week |

---

## Test Credentials (Sandbox)

| Role | Phone | Password |
|------|-------|----------|
| Admin | +963944000001 | password |
| Player | +963944111111 | password |
| Club Manager | +963944222222 | password |

**Test OTP:** `123456` (always succeeds in sandbox)

**MTN test number:** Any number starting with `+963933`
**Syriatel test number:** Any number starting with `+963944`

---

## API Documentation

Interactive API docs are available at:
- **Local:** `http://localhost:8000/api/documentation`
