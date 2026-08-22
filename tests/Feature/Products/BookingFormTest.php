<?php

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Coupon;
use App\Models\Product;
use Livewire\Livewire;

test('booking can be submitted with valid data', function () {
    $product = Product::factory()->create(['price' => 99.90]);

    Livewire::test('pages::products.booking-form', ['product' => $product])
        ->set('customer_name', 'Ali Bin Abu')
        ->set('customer_phone', '012-3456789')
        ->set('customer_email', 'ali@example.com')
        ->set('quantity', 2)
        ->call('placeBooking')
        ->assertHasNoErrors();

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
    $product = Product::factory()->create(['price' => 100]);
    $coupon = Coupon::factory()->percentage()->create(['code' => 'SAVE20', 'value' => 20]);

    Livewire::test('pages::products.booking-form', ['product' => $product])
        ->set('customer_name', 'Ali Bin Abu')
        ->set('customer_phone', '012-3456789')
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
    $product = Product::factory()->create(['price' => 10]);
    Coupon::factory()->fixed()->create(['code' => 'FLAT50', 'value' => 50]);

    Livewire::test('pages::products.booking-form', ['product' => $product])
        ->set('customer_name', 'Ali Bin Abu')
        ->set('customer_phone', '012-3456789')
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
    $product = Product::factory()->create(['price' => 99.90]);

    Livewire::test('pages::products.booking-form', ['product' => $product])
        ->set('customer_name', 'Ali Bin Abu')
        ->set('customer_phone', '012-3456789')
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
        ->set('couponCode', 'MAXEDOUT')
        ->call('placeBooking')
        ->assertHasErrors(['couponCode']);

    expect(Booking::count())->toBe(0);
    expect($coupon->fresh()->used_count)->toBe(1);
});
