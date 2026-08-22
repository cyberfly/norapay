<?php

namespace Database\Factories;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $unitPrice = fake()->randomFloat(2, 10, 500);
        $quantity = fake()->numberBetween(1, 3);

        return [
            'product_id' => Product::factory(),
            'product_name' => fake()->words(3, true),
            'unit_price' => $unitPrice,
            'quantity' => $quantity,
            'total_price' => $unitPrice * $quantity,
            'customer_name' => fake()->name(),
            'customer_phone' => fake()->numerify('01#-#######'),
            'customer_email' => fake()->safeEmail(),
            'customer_address' => fake()->address(),
            'notes' => null,
            'status' => BookingStatus::Pending,
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
        ];
    }
}
