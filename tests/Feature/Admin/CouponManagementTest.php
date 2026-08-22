<?php

use App\Enums\CouponType;
use App\Models\Coupon;
use App\Models\User;
use Livewire\Livewire;

test('guests are redirected away from the coupons index', function () {
    $this->get(route('admin.coupons.index'))->assertRedirect(route('login'));
});

test('authenticated user can view the coupons index', function () {
    $this->actingAs(User::factory()->admin()->create());

    $coupon = Coupon::factory()->create();

    $this->get(route('admin.coupons.index'))
        ->assertOk()
        ->assertSee($coupon->code);
});

test('authenticated user can create a fixed coupon', function () {
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test('pages::admin.coupons.form-modal')
        ->call('openForCreate')
        ->set('code', 'save10')
        ->set('type', CouponType::Fixed->value)
        ->set('value', '10.00')
        ->call('save')
        ->assertHasNoErrors();

    $coupon = Coupon::sole();

    expect($coupon->code)->toBe('SAVE10');
    expect($coupon->type)->toBe(CouponType::Fixed);
    expect((float) $coupon->value)->toBe(10.0);
    expect($coupon->is_active)->toBeTrue();
    expect($coupon->usage_limit)->toBeNull();
});

test('authenticated user can create a percentage coupon with a usage limit', function () {
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test('pages::admin.coupons.form-modal')
        ->call('openForCreate')
        ->set('code', 'PROMO20')
        ->set('type', CouponType::Percentage->value)
        ->set('value', '20')
        ->set('usage_limit', '100')
        ->call('save')
        ->assertHasNoErrors();

    $coupon = Coupon::sole();

    expect($coupon->type)->toBe(CouponType::Percentage);
    expect((float) $coupon->value)->toBe(20.0);
    expect($coupon->usage_limit)->toBe(100);
});

test('percentage coupon value cannot exceed 100', function () {
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test('pages::admin.coupons.form-modal')
        ->call('openForCreate')
        ->set('code', 'TOOBIG')
        ->set('type', CouponType::Percentage->value)
        ->set('value', '150')
        ->call('save')
        ->assertHasErrors(['value']);

    expect(Coupon::count())->toBe(0);
});

test('duplicate coupon codes are rejected', function () {
    $this->actingAs(User::factory()->admin()->create());

    Coupon::factory()->create(['code' => 'DUPE']);

    Livewire::test('pages::admin.coupons.form-modal')
        ->call('openForCreate')
        ->set('code', 'dupe')
        ->set('value', '5.00')
        ->call('save')
        ->assertHasErrors(['code']);

    expect(Coupon::count())->toBe(1);
});

test('authenticated user can edit a coupon', function () {
    $this->actingAs(User::factory()->admin()->create());

    $coupon = Coupon::factory()->create(['code' => 'OLDCODE']);

    Livewire::test('pages::admin.coupons.form-modal')
        ->call('openForEdit', $coupon->id)
        ->set('code', 'newcode')
        ->call('save')
        ->assertHasNoErrors();

    expect($coupon->fresh()->code)->toBe('NEWCODE');
});

test('authenticated user can search coupons by code', function () {
    $this->actingAs(User::factory()->admin()->create());

    $match = Coupon::factory()->create(['code' => 'SUMMER25']);
    $other = Coupon::factory()->create(['code' => 'WINTER10']);

    Livewire::test('pages::admin.coupons.index')
        ->set('search', 'SUMMER')
        ->assertSee($match->code)
        ->assertDontSee($other->code);
});

test('authenticated user can deactivate a coupon', function () {
    $this->actingAs(User::factory()->admin()->create());

    $coupon = Coupon::factory()->create(['is_active' => true]);

    Livewire::test('pages::admin.coupons.index')
        ->call('toggleActive', $coupon->id);

    expect($coupon->fresh()->is_active)->toBeFalse();
});
