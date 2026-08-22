<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::livewire('products', 'pages::products.index')->name('products.index');
Route::livewire('products/{product:slug}', 'pages::products.show')->name('products.show');

Route::livewire('payments/{booking:booking_number}/success', 'pages::payments.success')
    ->middleware('signed')
    ->name('payments.success');
Route::livewire('payments/{booking:booking_number}/failed', 'pages::payments.failed')
    ->middleware('signed')
    ->name('payments.failed');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('dashboard', 'pages::dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
require __DIR__.'/admin.php';
require __DIR__.'/webhooks.php';
