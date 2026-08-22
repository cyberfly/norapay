<?php

use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;

test('an unsigned success url is rejected', function () {
    $booking = Booking::factory()->create();

    $this->get(route('payments.success', ['booking' => $booking->booking_number]))
        ->assertForbidden();
});

test('a valid signed success url renders the booking', function () {
    $booking = Booking::factory()->create();

    $url = URL::signedRoute('payments.success', ['booking' => $booking->booking_number]);

    $this->get($url)
        ->assertOk()
        ->assertSee($booking->booking_number);
});

test('an unsigned failed url is rejected', function () {
    $booking = Booking::factory()->create();

    $this->get(route('payments.failed', ['booking' => $booking->booking_number]))
        ->assertForbidden();
});

test('a valid signed failed url renders the booking and allows retrying payment', function () {
    $booking = Booking::factory()->create();
    Payment::factory()->for($booking)->failed()->create();

    $url = URL::signedRoute('payments.failed', ['booking' => $booking->booking_number]);

    $this->get($url)
        ->assertOk()
        ->assertSee($booking->booking_number);

    $invoiceUrl = 'https://checkout-staging.xendit.co/web/retry-attempt';

    Http::fake([
        'https://api.xendit.co/v2/invoices' => Http::response([
            'id' => 'inv_retry',
            'invoice_url' => $invoiceUrl,
            'status' => 'PENDING',
        ]),
    ]);

    Livewire::test('pages::payments.failed', ['booking' => $booking])
        ->call('retryPayment')
        ->assertRedirect($invoiceUrl);

    expect(Payment::count())->toBe(2);
});
