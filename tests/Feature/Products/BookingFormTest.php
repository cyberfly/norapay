<?php

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Coupon;
use App\Models\Payment;
use App\Models\Product;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Livewire\Livewire;

function fakeXenditInvoice(array $overrides = []): string
{
    $invoiceUrl = 'https://checkout-staging.xendit.co/web/'.Str::random(10);

    Http::fake([
        'https://api.xendit.co/v2/invoices' => Http::response(array_merge([
            'id' => 'inv_'.Str::random(10),
            'invoice_url' => $invoiceUrl,
            'status' => 'PENDING',
        ], $overrides)),
    ]);

    return $invoiceUrl;
}

test('booking can be submitted with valid data', function () {
    $invoiceUrl = fakeXenditInvoice();

    $product = Product::factory()->create(['price' => 99.90]);

    Livewire::test('pages::products.booking-form', ['product' => $product])
        ->set('customer_name', 'Ali Bin Abu')
        ->set('customer_phone', '012-3456789')
        ->set('customer_email', 'ali@example.com')
        ->set('quantity', 2)
        ->call('placeBooking')
        ->assertHasNoErrors()
        ->assertRedirect($invoiceUrl);

    expect(Booking::count())->toBe(1);

    $booking = Booking::first();

    expect($booking->product_id)->toBe($product->id);
    expect($booking->product_name)->toBe($product->name);
    expect((float) $booking->unit_price)->toBe(99.90);
    expect($booking->quantity)->toBe(2);
    expect((float) $booking->total_price)->toBe(199.80);
    expect($booking->status)->toBe(BookingStatus::Pending);
});

test('booking form validates required fields', function () {
    $product = Product::factory()->create();

    Livewire::test('pages::products.booking-form', ['product' => $product])
        ->set('customer_name', '')
        ->set('customer_phone', '')
        ->call('placeBooking')
        ->assertHasErrors(['customer_name', 'customer_phone']);

    expect(Booking::count())->toBe(0);
});

test('booking form requires a customer email', function () {
    $product = Product::factory()->create();

    Livewire::test('pages::products.booking-form', ['product' => $product])
        ->set('customer_name', 'Ali Bin Abu')
        ->set('customer_phone', '012-3456789')
        ->set('customer_email', '')
        ->call('placeBooking')
        ->assertHasErrors(['customer_email']);

    expect(Booking::count())->toBe(0);
});

test('filling the honeypot field rejects the submission', function () {
    $product = Product::factory()->create();

    Livewire::test('pages::products.booking-form', ['product' => $product])
        ->set('customer_name', 'Ali Bin Abu')
        ->set('customer_phone', '012-3456789')
        ->set('website', 'https://spam.example.com')
        ->call('placeBooking')
        ->assertHasErrors(['website']);

    expect(Booking::count())->toBe(0);
});

test('a valid percentage coupon reduces the booking total', function () {
    fakeXenditInvoice();

    $product = Product::factory()->create(['price' => 100]);
    $coupon = Coupon::factory()->percentage()->create(['code' => 'SAVE20', 'value' => 20]);

    Livewire::test('pages::products.booking-form', ['product' => $product])
        ->set('customer_name', 'Ali Bin Abu')
        ->set('customer_phone', '012-3456789')
        ->set('customer_email', 'ali@example.com')
        ->set('quantity', 2)
        ->set('couponCode', 'save20')
        ->call('applyCoupon')
        ->call('placeBooking')
        ->assertHasNoErrors();

    $booking = Booking::sole();

    expect($booking->coupon_id)->toBe($coupon->id);
    expect($booking->coupon_code)->toBe('SAVE20');
    expect((float) $booking->discount_amount)->toBe(40.0);
    expect((float) $booking->total_price)->toBe(160.0);
    expect($coupon->fresh()->used_count)->toBe(1);
});

test('a valid fixed coupon reduces the booking total and never goes negative', function () {
    fakeXenditInvoice();

    $product = Product::factory()->create(['price' => 10]);
    Coupon::factory()->fixed()->create(['code' => 'FLAT50', 'value' => 50]);

    Livewire::test('pages::products.booking-form', ['product' => $product])
        ->set('customer_name', 'Ali Bin Abu')
        ->set('customer_phone', '012-3456789')
        ->set('customer_email', 'ali@example.com')
        ->set('quantity', 1)
        ->set('couponCode', 'FLAT50')
        ->call('applyCoupon')
        ->call('placeBooking')
        ->assertHasNoErrors();

    $booking = Booking::sole();

    expect((float) $booking->discount_amount)->toBe(10.0);
    expect((float) $booking->total_price)->toBe(0.0);
});

test('booking succeeds without a coupon code', function () {
    fakeXenditInvoice();

    $product = Product::factory()->create(['price' => 99.90]);

    Livewire::test('pages::products.booking-form', ['product' => $product])
        ->set('customer_name', 'Ali Bin Abu')
        ->set('customer_phone', '012-3456789')
        ->set('customer_email', 'ali@example.com')
        ->set('quantity', 1)
        ->call('placeBooking')
        ->assertHasNoErrors();

    $booking = Booking::sole();

    expect($booking->coupon_id)->toBeNull();
    expect($booking->coupon_code)->toBeNull();
    expect((float) $booking->discount_amount)->toBe(0.0);
    expect((float) $booking->total_price)->toBe(99.90);
});

test('an expired coupon is rejected at submission', function () {
    $product = Product::factory()->create(['price' => 50]);
    Coupon::factory()->expired()->create(['code' => 'EXPIRED']);

    Livewire::test('pages::products.booking-form', ['product' => $product])
        ->set('customer_name', 'Ali Bin Abu')
        ->set('customer_phone', '012-3456789')
        ->set('customer_email', 'ali@example.com')
        ->set('couponCode', 'EXPIRED')
        ->call('placeBooking')
        ->assertHasErrors(['couponCode']);

    expect(Booking::count())->toBe(0);
});

test('a coupon that has not started yet is rejected', function () {
    $product = Product::factory()->create(['price' => 50]);
    Coupon::factory()->notYetStarted()->create(['code' => 'FUTURE']);

    Livewire::test('pages::products.booking-form', ['product' => $product])
        ->set('customer_name', 'Ali Bin Abu')
        ->set('customer_phone', '012-3456789')
        ->set('couponCode', 'FUTURE')
        ->call('applyCoupon')
        ->assertSet('discountAmount', null);
});

test('an inactive coupon is rejected', function () {
    $product = Product::factory()->create(['price' => 50]);
    Coupon::factory()->inactive()->create(['code' => 'OFFCODE']);

    Livewire::test('pages::products.booking-form', ['product' => $product])
        ->set('customer_name', 'Ali Bin Abu')
        ->set('customer_phone', '012-3456789')
        ->set('customer_email', 'ali@example.com')
        ->set('couponCode', 'OFFCODE')
        ->call('placeBooking')
        ->assertHasErrors(['couponCode']);

    expect(Booking::count())->toBe(0);
});

test('a coupon that has reached its usage limit is rejected and used_count is unchanged', function () {
    $product = Product::factory()->create(['price' => 50]);
    $coupon = Coupon::factory()->usageLimitReached()->create(['code' => 'MAXEDOUT']);

    Livewire::test('pages::products.booking-form', ['product' => $product])
        ->set('customer_name', 'Ali Bin Abu')
        ->set('customer_phone', '012-3456789')
        ->set('customer_email', 'ali@example.com')
        ->set('couponCode', 'MAXEDOUT')
        ->call('placeBooking')
        ->assertHasErrors(['couponCode']);

    expect(Booking::count())->toBe(0);
    expect($coupon->fresh()->used_count)->toBe(1);
});

test('submitting a booking creates a pending payment linked to the booking', function () {
    fakeXenditInvoice();

    $product = Product::factory()->create(['price' => 50]);

    Livewire::test('pages::products.booking-form', ['product' => $product])
        ->set('customer_name', 'Ali Bin Abu')
        ->set('customer_phone', '012-3456789')
        ->set('customer_email', 'ali@example.com')
        ->call('placeBooking')
        ->assertHasNoErrors();

    $booking = Booking::sole();
    $payment = Payment::sole();

    expect($payment->booking_id)->toBe($booking->id);
    expect($payment->status)->toBe(PaymentStatus::Pending);
    expect($payment->external_id)->toBe("{$booking->booking_number}-P1");
    expect((float) $payment->amount)->toBe((float) $booking->total_price);
});

test('when xendit fails to create an invoice the booking still exists and payment is marked failed', function () {
    Http::fake([
        'https://api.xendit.co/v2/invoices' => Http::response(['message' => 'Invalid request'], 400),
    ]);

    $product = Product::factory()->create(['price' => 50]);

    $component = Livewire::test('pages::products.booking-form', ['product' => $product])
        ->set('customer_name', 'Ali Bin Abu')
        ->set('customer_phone', '012-3456789')
        ->set('customer_email', 'ali@example.com')
        ->call('placeBooking')
        ->assertHasNoErrors()
        ->assertNoRedirect();

    expect(Booking::count())->toBe(1);

    $payment = Payment::sole();
    expect($payment->status)->toBe(PaymentStatus::Failed);

    $component->assertSee('Cuba bayar semula');
});

test('retrying payment creates a new payment attempt with a distinct external id', function () {
    $invoiceUrl = 'https://checkout-staging.xendit.co/web/retry-attempt';

    Http::fakeSequence('https://api.xendit.co/v2/invoices')
        ->push(['message' => 'Invalid request'], 400)
        ->push(['id' => 'inv_retry', 'invoice_url' => $invoiceUrl, 'status' => 'PENDING']);

    $product = Product::factory()->create(['price' => 50]);

    $component = Livewire::test('pages::products.booking-form', ['product' => $product])
        ->set('customer_name', 'Ali Bin Abu')
        ->set('customer_phone', '012-3456789')
        ->set('customer_email', 'ali@example.com')
        ->call('placeBooking');

    expect(Payment::count())->toBe(1);

    $component->call('retryPayment')->assertRedirect($invoiceUrl);

    expect(Payment::count())->toBe(2);
    expect(Payment::latest('id')->first()->external_id)->toBe(Booking::sole()->booking_number.'-P2');
});
