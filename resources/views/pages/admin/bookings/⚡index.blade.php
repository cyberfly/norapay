<?php

use App\Enums\BookingStatus;
use App\Models\Booking;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    #[Url]
    public string $statusFilter = '';

    #[Url]
    public string $search = '';

    #[Computed]
    public function bookings()
    {
        return Booking::query()
            ->when($this->statusFilter, fn ($query) => $query->where('status', $this->statusFilter))
            ->when($this->search, function ($query) {
                $query->where(function ($query) {
                    $query->where('booking_number', 'like', '%'.$this->search.'%')
                        ->orWhere('customer_name', 'like', '%'.$this->search.'%')
                        ->orWhere('customer_phone', 'like', '%'.$this->search.'%')
                        ->orWhere('product_name', 'like', '%'.$this->search.'%');
                });
            })
            ->latest()
            ->paginate(15);
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }
}; ?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <flux:heading size="xl">{{ __('Orders') }}</flux:heading>

        <flux:select wire:model.live="statusFilter" class="max-w-xs">
            <flux:select.option value="">{{ __('All statuses') }}</flux:select.option>
            @foreach (BookingStatus::cases() as $status)
                <flux:select.option value="{{ $status->value }}">{{ $status->label() }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="{{ __('Search orders...') }}" clearable class="max-w-sm" />

    <flux:table :paginate="$this->bookings">
        <flux:table.columns>
            <flux:table.column>{{ __('Order #') }}</flux:table.column>
            <flux:table.column>{{ __('Customer') }}</flux:table.column>
            <flux:table.column>{{ __('Product') }}</flux:table.column>
            <flux:table.column>{{ __('Qty') }}</flux:table.column>
            <flux:table.column>{{ __('Total') }}</flux:table.column>
            <flux:table.column>{{ __('Status') }}</flux:table.column>
            <flux:table.column>{{ __('Date') }}</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->bookings as $booking)
                <flux:table.row wire:key="booking-{{ $booking->id }}">
                    <flux:table.cell>
                        <flux:link :href="route('admin.bookings.show', $booking)" wire:navigate>{{ $booking->booking_number }}</flux:link>
                    </flux:table.cell>
                    <flux:table.cell>{{ $booking->customer_name }}</flux:table.cell>
                    <flux:table.cell>{{ $booking->product_name }}</flux:table.cell>
                    <flux:table.cell>{{ $booking->quantity }}</flux:table.cell>
                    <flux:table.cell>{{ $booking->total_price }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge :color="$booking->status->color()" size="sm">{{ $booking->status->label() }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>{{ $booking->created_at?->format('d M Y H:i') }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:button size="sm" variant="ghost" icon="eye" :href="route('admin.bookings.show', $booking)" wire:navigate>
                            {{ __('View') }}
                        </flux:button>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="8">
                        <flux:text class="py-6 text-center">{{ __('No orders yet.') }}</flux:text>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</div>
