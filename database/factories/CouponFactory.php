<?php

namespace Database\Factories;

use App\Enums\CouponType;
use App\Models\Coupon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Coupon>
 */
class CouponFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('SAVE####')),
            'type' => CouponType::Fixed,
            'value' => fake()->randomFloat(2, 5, 50),
            'starts_at' => null,
            'expires_at' => null,
            'usage_limit' => null,
            'used_count' => 0,
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the coupon is a percentage discount.
     */
    public function percentage(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => CouponType::Percentage,
            'value' => fake()->numberBetween(5, 50),
        ]);
    }

    /**
     * Indicate that the coupon is a fixed-amount discount.
     */
    public function fixed(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => CouponType::Fixed,
            'value' => fake()->randomFloat(2, 5, 50),
        ]);
    }

    /**
     * Indicate that the coupon is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the coupon has already expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->subDay(),
        ]);
    }

    /**
     * Indicate that the coupon is not yet within its redemption window.
     */
    public function notYetStarted(): static
    {
        return $this->state(fn (array $attributes) => [
            'starts_at' => now()->addDay(),
        ]);
    }

    /**
     * Indicate that the coupon has reached its usage limit.
     */
    public function usageLimitReached(): static
    {
        return $this->state(fn (array $attributes) => [
            'usage_limit' => 1,
            'used_count' => 1,
        ]);
    }
}
