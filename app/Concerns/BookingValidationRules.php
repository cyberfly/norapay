<?php

namespace App\Concerns;

use Illuminate\Contracts\Validation\ValidationRule;

trait BookingValidationRules
{
    /**
     * Get the validation rules used to validate a booking submission.
     *
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function bookingRules(): array
    {
        return [
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:20', 'regex:/^[0-9+\-\s()]+$/'],
            'customer_email' => ['nullable', 'string', 'email', 'max:255'],
            'customer_address' => ['nullable', 'string', 'max:1000'],
            'quantity' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'website' => ['prohibited'],
        ];
    }
}
