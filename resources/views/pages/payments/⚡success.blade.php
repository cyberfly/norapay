<?php

use App\Models\Booking;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts::public')] class extends Component
{
    public Booking $booking;

    public function mount(Booking $booking): void
    {
        $this->booking = $booking;
    }
}; ?>

<div class="py-10 sm:py-14">
    <div class="mx-auto max-w-md space-y-3 rounded-2xl border border-zinc-200 bg-white p-8 text-center shadow-sm">
        <span class="mx-auto flex size-12 items-center justify-center rounded-full bg-emerald-50 text-emerald-600">
            <flux:icon name="check" class="size-6" />
        </span>

        <flux:heading size="lg">{{ __('Terima kasih!') }}</flux:heading>
        <flux:text>{{ __('Pembayaran anda sedang disahkan. Kami akan hubungi anda tidak lama lagi.') }}</flux:text>

        <div class="inline-flex items-center gap-2 rounded-full border border-dashed border-zinc-300 px-4 py-1.5">
            <span class="font-mono text-xs tracking-widest text-zinc-400 uppercase">{{ __('No. Tempahan') }}</span>
            <span class="font-mono text-sm font-semibold text-zinc-900">{{ $booking->booking_number }}</span>
        </div>

        <div>
            <flux:button variant="ghost" :href="route('products.index')" class="mt-2">
                {{ __('Kembali ke produk') }}
            </flux:button>
        </div>
    </div>
</div>
