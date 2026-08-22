<?php

use App\Models\Product;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts::public')] class extends Component
{
    public Product $product;

    public function mount(Product $product): void
    {
        abort_unless($product->is_active, 404);

        $this->product = $product;
    }
}; ?>

<div class="py-10 sm:py-14">
    <div class="grid gap-0 overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm lg:grid-cols-2">
        <div class="relative">
            @if ($product->image)
                <img
                    src="{{ Storage::disk('public')->url($product->image) }}"
                    alt="{{ $product->name }}"
                    class="aspect-square w-full object-cover lg:aspect-auto lg:h-full"
                />
            @else
                <div class="relative aspect-square w-full overflow-hidden bg-zinc-50 lg:aspect-auto lg:h-full">
                    <x-placeholder-pattern class="absolute inset-0 size-full stroke-zinc-900/10" />
                </div>
            @endif
        </div>

        <div class="flex flex-col p-6 sm:p-10">
            <div class="flex-1 space-y-5">
                <div>
                    <span class="font-mono text-xs font-medium tracking-widest text-zinc-400 uppercase">{{ config('app.name', 'Norapay') }} &middot; {{ __('Laman produk') }}</span>

                    <flux:heading size="xl" class="mt-2 text-balance">{{ $product->name }}</flux:heading>

                    @if ($product->short_description)
                        <flux:text class="mt-2 text-balance" size="lg">{{ $product->short_description }}</flux:text>
                    @endif
                </div>

                <div class="inline-flex items-baseline gap-1 rounded-lg bg-emerald-50 px-4 py-2.5 text-emerald-800">
                    <span class="font-mono text-sm font-medium">{{ $product->currency }}</span>
                    <span class="text-3xl font-semibold tracking-tight">{{ number_format((float) $product->price, 2) }}</span>
                </div>

                @if ($product->description)
                    <div class="rich-text border-t border-zinc-100 pt-5">{!! $product->description !!}</div>
                @endif
            </div>

            <div class="ticket-seam mt-8 pt-8 [--seam-bg:var(--color-white)]">
                <livewire:pages::products.booking-form :product="$product" />
            </div>
        </div>
    </div>
</div>
