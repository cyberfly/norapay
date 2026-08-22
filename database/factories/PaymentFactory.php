<?php

namespace Database\Factories;

use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'booking_id' => Booking::factory(),
            'external_id' => strtoupper(Str::random(20)),
            'xendit_invoice_id' => null,
            'status' => PaymentStatus::Pending,
            'amount' => fake()->randomFloat(2, 10, 500),
            'currency' => 'MYR',
            'payment_method' => null,
            'invoice_url' => 'https://checkout-staging.xendit.co/web/'.fake()->uuid(),
            'raw_payload' => null,
            'expiry_date' => now()->addDay(),
            'paid_at' => null,
        ];
    }

    /**
     * Indicate that the payment has been paid.
     */
    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::Paid,
            'payment_method' => 'CREDIT_CARD',
            'paid_at' => now(),
        ]);
    }

    /**
     * Indicate that the payment has expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::Expired,
            'expiry_date' => now()->subDay(),
        ]);
    }

    /**
     * Indicate that the payment failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::Failed,
        ]);
    }
}
