---
paths:
  - routes/web.php
  - routes/admin.php
---

# Routes

## payments.success / payments.failed routes require signed URLs
Both routes bind Booking by booking_number (guessable, 6-char random suffix) and are middleware('signed') — only reachable via a URL::signedRoute() link (built in App\Concerns\InitiatesXenditPayment as the Xendit success_redirect_url/failure_redirect_url). Don't link to these routes with a plain route() call; use URL::signedRoute() with an expiry.

## Admin routes require the access-admin gate, not just auth
routes/admin.php group must include `can:access-admin` alongside `auth`/`verified` — "admin" here is only a route prefix/name, there is no separate guard. The gate is `App\Providers\AppServiceProvider::boot()`, checking `User::is_admin` (boolean column added in 2026_08_22_091112_add_is_admin_to_users_table). Registration is open (Fortify), so without this gate any verified user could reach admin pages. `is_admin` is intentionally excluded from User's #[Fillable] to block mass-assignment escalation; set it via `User::factory()->admin()` in tests/seeders.
