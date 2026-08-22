<?php

use App\Models\Product;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts::public')] class extends Component
{
    use WithPagination;

    public function with(): array
    {
        return [
            'products' => Product::query()
                ->active()
                ->latest()
                ->paginate(12),
        ];
    }
}; ?>

<div class="py-10 sm:py-14">
    <div class="max-w-xl">
        <span class="font-mono text-xs font-medium tracking-widest text-zinc-400 uppercase">{{ __('Katalog produk') }}</span>
        <flux:heading size="xl" class="mt-2">{{ __('Semua produk') }}</flux:heading>
        <flux:text class="mt-2" size="lg">{{ __('Pilih produk untuk lihat butiran dan buat tempahan.') }}</flux:text>
    </div>

    @if ($products->isEmpty())
        <div class="mt-12 rounded-2xl border border-dashed border-zinc-300 px-6 py-16 text-center">
            <flux:icon name="shopping-bag" class="mx-auto size-8 text-zinc-300" />
            <flux:heading size="lg" class="mt-4">{{ __('Belum ada produk') }}</flux:heading>
            <flux:text class="mt-1.5">{{ __('Sila kembali sebentar lagi.') }}</flux:text>
        </div>
    @else
        <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($products as $product)
                <a
                    href="{{ route('products.show', $product) }}"
                    wire:navigate
                    class="group overflow-hidden rounded-2xl border border-zinc-200 bg-white transition hover:border-zinc-300 hover:shadow-md"
                >
                    @if ($product->image)
                        <img
                            src="{{ Storage::disk('public')->url($product->image) }}"
                            alt="{{ $product->name }}"
                            class="aspect-square w-full object-cover"
                        />
                    @else
                        <div class="relative aspect-square w-full overflow-hidden bg-zinc-50">
                            <x-placeholder-pattern class="absolute inset-0 size-full stroke-zinc-900/10" />
                        </div>
                    @endif

                    <div class="p-5">
                        <flux:heading class="truncate transition group-hover:text-zinc-600">{{ $product->name }}</flux:heading>

                        @if ($product->short_description)
                            <flux:text class="mt-1 line-clamp-2">{{ $product->short_description }}</flux:text>
                        @endif

                        <p class="mt-3 inline-flex items-baseline gap-1 rounded-full bg-emerald-50 px-2.5 py-0.5 text-emerald-800">
                            <span class="font-mono text-xs">{{ $product->currency }}</span>
                            <span class="text-sm font-semibold">{{ number_format((float) $product->price, 2) }}</span>
                        </p>
                    </div>
                </a>
            @endforeach
        </div>

        <div class="mt-10">
            {{ $products->links() }}
        </div>
    @endif
</div>
