<?php

use App\Models\Product;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

test('guests are redirected away from the products index', function () {
    $this->get(route('admin.products.index'))->assertRedirect(route('login'));
});

test('non-admin users cannot view the products index', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('admin.products.index'))->assertForbidden();
});

test('non-admin users cannot open the products index component', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test('pages::admin.products.index')->assertForbidden();
});

test('non-admin users cannot open the product form modal', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test('pages::admin.products.form-modal')->assertForbidden();

    expect(Product::count())->toBe(0);
});

test('admin can view the products index', function () {
    $this->actingAs(User::factory()->admin()->create());

    $product = Product::factory()->create();

    $this->get(route('admin.products.index'))
        ->assertOk()
        ->assertSee($product->name);
});

test('admin can create a product', function () {
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test('pages::admin.products.form-modal')
        ->call('openForCreate')
        ->set('name', 'New Product')
        ->set('price', '49.90')
        ->call('save')
        ->assertHasNoErrors();

    $product = Product::sole();

    expect($product->name)->toBe('New Product');
    expect($product->slug)->toBe('new-product');
    expect((float) $product->price)->toBe(49.90);
    expect($product->is_active)->toBeTrue();
});

test('admin can edit a product', function () {
    $this->actingAs(User::factory()->admin()->create());

    $product = Product::factory()->create(['name' => 'Old Name']);

    Livewire::test('pages::admin.products.form-modal')
        ->call('openForEdit', $product->id)
        ->set('name', 'Updated Name')
        ->call('save')
        ->assertHasNoErrors();

    expect($product->fresh()->name)->toBe('Updated Name');
});

test('admin can upload a featured image for a product', function () {
    Storage::fake('public');

    $this->actingAs(User::factory()->admin()->create());

    Livewire::test('pages::admin.products.form-modal')
        ->call('openForCreate')
        ->set('name', 'Product With Image')
        ->set('price', '19.90')
        ->set('photo', UploadedFile::fake()->image('featured.jpg'))
        ->call('save')
        ->assertHasNoErrors();

    $product = Product::sole();

    expect($product->image)->not->toBeNull();
    Storage::disk('public')->assertExists($product->image);
});

test('admin can remove an existing featured image', function () {
    Storage::fake('public');

    $this->actingAs(User::factory()->admin()->create());

    $product = Product::factory()->create(['image' => 'products/existing.jpg']);
    Storage::disk('public')->put('products/existing.jpg', 'fake-contents');

    Livewire::test('pages::admin.products.form-modal')
        ->call('openForEdit', $product->id)
        ->call('removeImage')
        ->call('save')
        ->assertHasNoErrors();

    expect($product->fresh()->image)->toBeNull();
    Storage::disk('public')->assertMissing('products/existing.jpg');
});

test('admin can search products by name', function () {
    $this->actingAs(User::factory()->admin()->create());

    $match = Product::factory()->create(['name' => 'Wireless Mouse']);
    $other = Product::factory()->create(['name' => 'Keyboard']);

    Livewire::test('pages::admin.products.index')
        ->set('search', 'Wireless')
        ->assertSee($match->name)
        ->assertDontSee($other->name);
});

test('admin can deactivate a product', function () {
    $this->actingAs(User::factory()->admin()->create());

    $product = Product::factory()->create(['is_active' => true]);

    Livewire::test('pages::admin.products.index')
        ->call('toggleActive', $product->id);

    expect($product->fresh()->is_active)->toBeFalse();
});
