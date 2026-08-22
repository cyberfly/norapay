<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('admin')->name('admin.')->group(function () {
    Route::livewire('products', 'pages::admin.products.index')->name('products.index');

    Route::livewire('coupons', 'pages::admin.coupons.index')->name('coupons.index');

    Route::livewire('bookings', 'pages::admin.bookings.index')->name('bookings.index');
    Route::livewire('bookings/{booking}', 'pages::admin.bookings.show')->name('bookings.show');
});
