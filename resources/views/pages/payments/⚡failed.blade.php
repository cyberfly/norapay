<?php

use App\Concerns\InitiatesXenditPayment;
use App\Models\Booking;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts::public')] class extends Component
{
    use InitiatesXenditPayment;

    public Booking $booking;

    public function mount(Booking $booking): void
    {
        $this->booking = $booking;
    }

    public function retryPayment(): void
    {
        $this->initiatePayment($this->booking->fresh());
    }
}; ?>

<div class="py-10 sm:py-14">
    <div class="mx-auto max-w-md space-y-3 rounded-2xl border border-zinc-200 bg-white p-8 text-center shadow-sm">
        <span class="mx-auto flex size-12 items-center justify-center rounded-full bg-red-50 text-red-600">
            <flux:icon name="x-mark" class="size-6" />
        </span>

        <flux:heading size="lg">{{ __('Pembayaran tidak berjaya') }}</flux:heading>
        <flux:text>{{ __('Pembayaran anda tidak dapat diselesaikan. Sila cuba lagi.') }}</flux:text>

        <div class="inline-flex items-center gap-2 rounded-full border border-dashed border-zinc-300 px-4 py-1.5">
            <span class="font-mono text-xs tracking-widest text-zinc-400 uppercase">{{ __('No. Tempahan') }}</span>
            <span class="font-mono text-sm font-semibold text-zinc-900">{{ $booking->booking_number }}</span>
        </div>

        <div>
            <flux:button variant="primary" wire:click="retryPayment" class="mt-2">
                {{ __('Cuba bayar semula') }}
            </flux:button>
        </div>
    </div>
</div>
