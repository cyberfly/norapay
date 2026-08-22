<?php

use App\Enums\BookingStatus;
use App\Models\Booking;
use Flux\Flux;
use Livewire\Component;

new class extends Component
{
    public Booking $booking;

    public string $status = '';

    public function mount(Booking $booking): void
    {
        $this->booking = $booking;
        $this->status = $booking->status->value;
    }

    public function updateStatus(): void
    {
        $this->validate([
            'status' => ['required', 'string', 'in:'.implode(',', array_column(BookingStatus::cases(), 'value'))],
        ]);

        $this->booking->update(['status' => $this->status]);

        Flux::toast(variant: 'success', text: __('Order status updated.'));
    }
}; ?>

<div class="max-w-2xl space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">{{ $booking->booking_number }}</flux:heading>
            <flux:text>{{ $booking->created_at?->format('d M Y H:i') }}</flux:text>
        </div>

        <flux:badge :color="$booking->status->color()">{{ $booking->status->label() }}</flux:badge>
    </div>

    <div class="grid gap-6 rounded-xl border border-neutral-200 p-6 dark:border-neutral-700 sm:grid-cols-2">
        <div>
            <flux:heading size="sm">{{ __('Customer') }}</flux:heading>
            <flux:text>{{ $booking->customer_name }}</flux:text>
            <flux:text>{{ $booking->customer_phone }}</flux:text>
            @if ($booking->customer_email)
                <flux:text>{{ $booking->customer_email }}</flux:text>
            @endif
            @if ($booking->customer_address)
                <flux:text class="whitespace-pre-line">{{ $booking->customer_address }}</flux:text>
            @endif
        </div>

        <div>
            <flux:heading size="sm">{{ __('Order') }}</flux:heading>
            <flux:text>{{ $booking->product_name }}</flux:text>
            <flux:text>{{ __('Qty') }}: {{ $booking->quantity }}</flux:text>
            <flux:text>{{ __('Unit price') }}: {{ number_format((float) $booking->unit_price, 2) }}</flux:text>
            @if ((float) $booking->discount_amount > 0)
                <flux:text class="text-emerald-600">{{ __('Discount') }} ({{ $booking->coupon_code }}): -{{ number_format((float) $booking->discount_amount, 2) }}</flux:text>
            @endif
            <flux:text>{{ __('Total') }}: {{ number_format((float) $booking->total_price, 2) }}</flux:text>
        </div>

        @if ($booking->notes)
            <div class="sm:col-span-2">
                <flux:heading size="sm">{{ __('Notes') }}</flux:heading>
                <flux:text class="whitespace-pre-line">{{ $booking->notes }}</flux:text>
            </div>
        @endif
    </div>

    <form wire:submit="updateStatus" class="flex items-end gap-4">
        <flux:select wire:model="status" :label="__('Status')" class="max-w-xs">
            @foreach (BookingStatus::cases() as $case)
                <flux:select.option value="{{ $case->value }}">{{ $case->label() }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:button variant="primary" type="submit">{{ __('Update status') }}</flux:button>
    </form>
</div>
