<?php

use App\Models\Product;

test('active product landing page is displayed', function () {
    $product = Product::factory()->create();

    $this->get(route('products.show', $product))
        ->assertOk()
        ->assertSee($product->name);
});

test('inactive product landing page returns 404', function () {
    $product = Product::factory()->inactive()->create();

    $this->get(route('products.show', $product))->assertNotFound();
});

test('unknown product slug returns 404', function () {
    $this->get('/products/does-not-exist')->assertNotFound();
});
