---
paths:
  - app/Http/Controllers/XenditWebhookController.php
---

# Controllers

## Xendit webhook route is CSRF-exempt and lives in routes/webhooks.php
POST /webhooks/xendit is excluded from CSRF verification in bootstrap/app.php via $middleware->validateCsrfTokens(except: ['webhooks/xendit']). Auth is via the x-callback-token header (XenditClient::verifyCallbackToken), not a body signature. The handler is idempotent: it locks the matching Payment row and no-ops if the mapped PaymentStatus is unchanged, since Xendit retries deliveries.
