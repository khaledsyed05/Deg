# Webhook Documentation — Payment Gateways

## Overview

Payment gateways send HTTP POST callbacks to notify about payment status changes.
All webhooks are verified using HMAC-SHA256 signatures before processing.

## Endpoints

| Gateway | URL | Signature Header |
|---------|-----|------------------|
| MTN Cash | `POST /api/webhooks/mtn/callback` | `X-MTN-Signature` |
| Syriatel Cash | `POST /api/webhooks/syriatel/callback` | `X-Signature` |
| Fatora | `POST /api/webhooks/fatora/callback` | `X-Fatora-Signature` |
| SamaPay | `POST /api/webhooks/samapay/callback` | `X-SamaPay-Signature` |

---

## MTN Cash

**Payload:**
```json
{
  "transaction_id": "MTN123456",
  "status": "SUCCESS",
  "amount": 15000,
  "reference": "BK12345"
}
```

**Signature:**
```php
$signature = hash_hmac('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE), $webhookSecret);
```

**Statuses:**
| Gateway Status | Internal Status |
|---------------|-----------------|
| `SUCCESS`, `COMPLETED` | `completed` |
| `PROCESSING` | `processing` |
| `FAILED`, `REJECTED` | `failed` |
| `CANCELLED` | `cancelled` |

---

## Syriatel Cash

**Payload:**
```json
{
  "transaction_reference": "SYR789012",
  "transaction_status": "SUCCESSFUL",
  "amount": 15000,
  "order_id": "BK12345"
}
```

**Signature** (concatenated string, not JSON):
```php
$dataString = $payload['transaction_reference'] . $payload['order_id'] . $payload['amount'];
$signature = hash_hmac('sha256', $dataString, $webhookSecret);
```

**Statuses:**
| Gateway Status | Internal Status |
|---------------|-----------------|
| `SUCCESSFUL`, `APPROVED` | `completed` |
| `PROCESSING` | `processing` |
| `FAILED`, `DECLINED` | `failed` |
| `CANCELLED` | `cancelled` |

---

## Fatora

**Payload:**
```json
{
  "InvoiceId": "FAT456789",
  "status": "paid",
  "amount": 15000,
  "order_id": "BK12345"
}
```

**Signature:**
```php
$signature = hash_hmac('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE), $webhookSecret);
```

**Statuses:**
| Gateway Status | Internal Status |
|---------------|-----------------|
| `paid`, `success` | `completed` |
| `expired`, `failed` | `failed` |
| `cancelled` | `cancelled` |

---

## SamaPay

**Payload:**
```json
{
  "payment_id": "SP000123",
  "status": "paid",
  "amount": 15000,
  "order_id": "BK12345"
}
```

**Signature:**
```php
$signature = hash_hmac('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE), $webhookSecret);
```

---

## Response Requirements

Your server must respond within 5 seconds.

**Success (200):**
```
OK
```

**Signature failure (401):**
```
Unauthorized
```

---

## Retry Policy

Gateways retry failed webhooks (non-2xx responses) with exponential backoff:

| Attempt | Delay |
|---------|-------|
| 1st | 1 minute |
| 2nd | 5 minutes |
| 3rd | 15 minutes |
| 4th | 1 hour |
| 5th | 6 hours |

Always return `200` quickly to avoid retries. Defer heavy processing to queued jobs.

---

## What Happens on Webhook Receipt

1. Signature is verified using HMAC-SHA256
2. Payload is normalized to `{status, transaction_id, amount, reference}`
3. `Payment` record is looked up by `provider_transaction_id`
4. `Payment.status` is updated to the normalized status
5. On `completed`: `Booking.deposit_status` is set to `paid`

---

## Testing Webhooks Locally

Use **ngrok** to expose your local server:

```bash
ngrok http 8000
# Use the https URL in gateway config:
# https://abc123.ngrok.io/api/webhooks/mtn/callback
```

**cURL test (MTN):**
```bash
PAYLOAD='{"transaction_id":"TEST123","status":"SUCCESS","amount":15000,"reference":"BK12345"}'
SECRET="your-webhook-secret"
SIGNATURE=$(echo -n "$PAYLOAD" | openssl dgst -sha256 -hmac "$SECRET" | awk '{print $2}')

curl -X POST http://localhost:8000/api/webhooks/mtn/callback \
  -H "Content-Type: application/json" \
  -H "X-MTN-Signature: $SIGNATURE" \
  -d "$PAYLOAD"
```

---

## Environment Variables

```env
PAYMENT_MTN_WEBHOOK_SECRET=your-mtn-secret
PAYMENT_SYRIATEL_WEBHOOK_SECRET=your-syriatel-secret
PAYMENT_FATORA_WEBHOOK_SECRET=your-fatora-secret
PAYMENT_SAMAPAY_WEBHOOK_SECRET=your-samapay-secret
```
