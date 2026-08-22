<?php

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Payment;

beforeEach(function () {
    config(['services.xendit.callback_verification_token' => 'test-callback-token']);
});

function postXenditWebhook(array $payload, ?string $token = 'test-callback-token')
{
    return test()->postJson('/webhooks/xendit', $payload, array_filter([
        'x-callback-token' => $token,
    ]));
}

test('a missing callback token is rejected', function () {
    $payment = Payment::factory()->create();

    postXenditWebhook(['external_id' => $payment->external_id, 'status' => 'PAID'], token: null)
        ->assertForbidden();

    expect($payment->fresh()->status)->toBe(PaymentStatus::Pending);
});

test('an invalid callback token is rejected', function () {
    $payment = Payment::factory()->create();

    postXenditWebhook(['external_id' => $payment->external_id, 'status' => 'PAID'], token: 'wrong-token')
        ->assertForbidden();

    expect($payment->fresh()->status)->toBe(PaymentStatus::Pending);
});

test('an unknown external_id is acknowledged without mutating anything', function () {
    postXenditWebhook(['external_id' => 'does-not-exist', 'status' => 'PAID'])
        ->assertOk();

    expect(Payment::count())->toBe(0);
});

test('a paid callback marks the payment paid and confirms the booking', function () {
    $booking = Booking::factory()->create(['status' => BookingStatus::Pending]);
    $payment = Payment::factory()->for($booking)->create(['status' => PaymentStatus::Pending]);

    postXenditWebhook([
        'id' => 'inv_123',
        'external_id' => $payment->external_id,
        'status' => 'PAID',
        'payment_method' => 'CREDIT_CARD',
    ])->assertOk();

    $payment->refresh();
    expect($payment->status)->toBe(PaymentStatus::Paid);
    expect($payment->xendit_invoice_id)->toBe('inv_123');
    expect($payment->payment_method)->toBe('CREDIT_CARD');
    expect($payment->paid_at)->not->toBeNull();

    expect($booking->fresh()->status)->toBe(BookingStatus::Confirmed);
});

test('a settled callback also marks the payment paid', function () {
    $payment = Payment::factory()->create(['status' => PaymentStatus::Pending]);

    postXenditWebhook(['id' => 'inv_123', 'external_id' => $payment->external_id, 'status' => 'SETTLED'])
        ->assertOk();

    expect($payment->fresh()->status)->toBe(PaymentStatus::Paid);
});

test('an expired callback marks the payment expired and leaves the booking pending', function () {
    $booking = Booking::factory()->create(['status' => BookingStatus::Pending]);
    $payment = Payment::factory()->for($booking)->create(['status' => PaymentStatus::Pending]);

    postXenditWebhook(['id' => 'inv_123', 'external_id' => $payment->external_id, 'status' => 'EXPIRED'])
        ->assertOk();

    expect($payment->fresh()->status)->toBe(PaymentStatus::Expired);
    expect($booking->fresh()->status)->toBe(BookingStatus::Pending);
});

test('a duplicate delivery of the same status is a no-op', function () {
    $payment = Payment::factory()->paid()->create();
    $originalUpdatedAt = $payment->updated_at;

    postXenditWebhook(['id' => 'inv_123', 'external_id' => $payment->external_id, 'status' => 'PAID'])
        ->assertOk();

    expect($payment->fresh()->updated_at->equalTo($originalUpdatedAt))->toBeTrue();
});
