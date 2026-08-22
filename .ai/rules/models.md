---
paths:
  - 'app/Models/{Booking,Payment}.php'
---

# Models

## Payment belongsTo Booking; BookingStatus untouched by payment lifecycle
Payment (its own table, PaymentStatus enum: Pending/Paid/Expired/Failed) belongsTo Booking, not the other way around — a Booking can have multiple Payment attempts (retries mint a new external_id "{booking_number}-P{n}" rather than resending the same invoice request). Booking::latestPayment() (hasOne latestOfMany) is the read path for "is this booking paid". BookingStatus keeps its original Pending→Confirmed/Completed/Cancelled lifecycle; only the Xendit webhook flips it to Confirmed (on a Paid payment) or an admin manually changes it — payment attempts never touch BookingStatus directly.
