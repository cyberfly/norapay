<?php

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\User;
use Livewire\Livewire;

test('guests are redirected away from the bookings index', function () {
    $this->get(route('admin.bookings.index'))->assertRedirect(route('login'));
});

test('authenticated user can view the bookings index', function () {
    $this->actingAs(User::factory()->create());

    $booking = Booking::factory()->create();

    $this->get(route('admin.bookings.index'))
        ->assertOk()
        ->assertSee($booking->booking_number);
});

test('authenticated user can view a booking detail page', function () {
    $this->actingAs(User::factory()->create());

    $booking = Booking::factory()->create();

    $this->get(route('admin.bookings.show', $booking))
        ->assertOk()
        ->assertSee($booking->customer_name);
});

test('authenticated user can search bookings', function () {
    $this->actingAs(User::factory()->create());

    $matching = Booking::factory()->create(['customer_name' => 'Ali Bin Abu']);
    $other = Booking::factory()->create(['customer_name' => 'Siti Zainab']);

    Livewire::test('pages::admin.bookings.index')
        ->set('search', 'Ali')
        ->assertSee($matching->booking_number)
        ->assertDontSee($other->booking_number);
});

test('authenticated user can update a booking status', function () {
    $this->actingAs(User::factory()->create());

    $booking = Booking::factory()->create(['status' => BookingStatus::Pending]);

    Livewire::test('pages::admin.bookings.show', ['booking' => $booking])
        ->set('status', BookingStatus::Confirmed->value)
        ->call('updateStatus')
        ->assertHasNoErrors();

    expect($booking->fresh()->status)->toBe(BookingStatus::Confirmed);
});
