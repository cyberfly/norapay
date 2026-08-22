---
paths:
  - routes/web.php
---

# Routes

## payments.success / payments.failed routes require signed URLs
Both routes bind Booking by booking_number (guessable, 6-char random suffix) and are middleware('signed') — only reachable via a URL::signedRoute() link (built in App\Concerns\InitiatesXenditPayment as the Xendit success_redirect_url/failure_redirect_url). Don't link to these routes with a plain route() call; use URL::signedRoute() with an expiry.
