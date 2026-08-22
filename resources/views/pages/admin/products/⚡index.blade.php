<?php

use App\Models\Product;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Computed]
    public function products()
    {
        return Product::query()
            ->when($this->search, fn ($query) => $query->where('name', 'like', '%'.$this->search.'%'))
            ->latest()
            ->paginate(10);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function toggleActive(int $productId): void
    {
        $product = Product::findOrFail($productId);

        $product->update(['is_active' => ! $product->is_active]);
    }

    #[On('product-saved')]
    public function refreshProducts(): void
    {
        unset($this->products);
    }
}; ?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <flux:heading size="xl">{{ __('Products') }}</flux:heading>

        <flux:modal.trigger name="product-form">
            <flux:button variant="primary" icon="plus" wire:click="$dispatch('create-product')">
                {{ __('New product') }}
            </flux:button>
        </flux:modal.trigger>
    </div>

    <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="{{ __('Search products...') }}" clearable class="max-w-sm" />

    <flux:table :paginate="$this->products">
        <flux:table.columns>
            <flux:table.column>{{ __('Name') }}</flux:table.column>
            <flux:table.column>{{ __('Price') }}</flux:table.column>
            <flux:table.column>{{ __('Status') }}</flux:table.column>
            <flux:table.column>{{ __('Created') }}</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->products as $product)
                <flux:table.row wire:key="product-{{ $product->id }}">
                    <flux:table.cell>
                        <flux:link :href="route('products.show', $product)" target="_blank">{{ $product->name }}</flux:link>
                    </flux:table.cell>
                    <flux:table.cell>{{ $product->currency }} {{ number_format((float) $product->price, 2) }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge :color="$product->is_active ? 'green' : 'zinc'" size="sm">
                            {{ $product->is_active ? __('Active') : __('Inactive') }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>{{ $product->created_at?->format('d M Y') }}</flux:table.cell>
                    <flux:table.cell>
                        <div class="flex justify-end gap-2">
                            <flux:button size="sm" variant="ghost" icon="arrow-top-right-on-square" :href="route('products.show', $product)" target="_blank">
                                {{ __('View') }}
                            </flux:button>

                            <flux:modal.trigger name="product-form">
                                <flux:button size="sm" wire:click="$dispatch('edit-product', { productId: {{ $product->id }} })">
                                    {{ __('Edit') }}
                                </flux:button>
                            </flux:modal.trigger>

                            <flux:button size="sm" variant="ghost" wire:click="toggleActive({{ $product->id }})">
                                {{ $product->is_active ? __('Deactivate') : __('Activate') }}
                            </flux:button>
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="5">
                        <flux:text class="py-6 text-center">{{ __('No products yet.') }}</flux:text>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    <livewire:pages::admin.products.form-modal />
</div>
