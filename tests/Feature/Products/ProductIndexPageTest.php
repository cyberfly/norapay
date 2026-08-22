<?php

use App\Models\Product;

test('product index page lists active products', function () {
    $active = Product::factory()->create(['name' => 'Kasut Sukan Ultra']);
    Product::factory()->inactive()->create(['name' => 'Produk Tidak Aktif']);

    $this->get(route('products.index'))
        ->assertOk()
        ->assertSee($active->name)
        ->assertDontSee('Produk Tidak Aktif');
});

test('product index page shows empty state when there are no active products', function () {
    $this->get(route('products.index'))
        ->assertOk()
        ->assertSee('Belum ada produk');
});
