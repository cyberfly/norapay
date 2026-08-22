---
paths:
  - 'resources/views/pages/admin/**'
---

# Admin

## Also authorize inside admin Livewire components' mount()
Route middleware (`can:access-admin`) protects the initial page load and, in real requests, Livewire replays it on subsequent /livewire/update calls via Livewire's PersistentMiddleware (Authorize is on its allowlist). But `Livewire::test()` explicitly skips persistent-middleware replay, so component tests won't catch a missing gate that way. New/edited admin Livewire full-page components (see products/⚡index.blade.php, products/⚡form-modal.blade.php) should call `Gate::authorize('access-admin')` in `mount()` for defense-in-depth and testability — bookings/coupons components don't have this yet and rely solely on route middleware.
