---
paths:
  - 'app/Services/Xendit/**'
---

# Xendit

## Xendit payment integration via raw HTTP, no SDK
Payments are handled by App\Services\Xendit\XenditClient (Laravel Http facade against the Xendit Invoice API), not the xendit/xendit-php composer package — that dependency was deliberately not added. Bound as a singleton in AppServiceProvider::register(), configured entirely from config('services.xendit.*') (never env() inside the class). App\Concerns\InitiatesXenditPayment is the shared trait for Livewire components that create a Payment and redirect to Xendit (used by the booking form and the payments.failed retry page) — reuse it rather than duplicating invoice-creation logic.
