<?php

use App\Enums\CouponType;
use App\Models\Coupon;
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
    public function coupons()
    {
        return Coupon::query()
            ->when($this->search, fn ($query) => $query->where('code', 'like', '%'.strtoupper($this->search).'%'))
            ->latest()
            ->paginate(10);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function toggleActive(int $couponId): void
    {
        $coupon = Coupon::findOrFail($couponId);

        $coupon->update(['is_active' => ! $coupon->is_active]);
    }

    #[On('coupon-saved')]
    public function refreshCoupons(): void
    {
        unset($this->coupons);
    }
}; ?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <flux:heading size="xl">{{ __('Coupons') }}</flux:heading>

        <flux:modal.trigger name="coupon-form">
            <flux:button variant="primary" icon="plus" wire:click="$dispatch('create-coupon')">
                {{ __('New coupon') }}
            </flux:button>
        </flux:modal.trigger>
    </div>

    <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="{{ __('Search coupons...') }}" clearable class="max-w-sm" />

    <flux:table :paginate="$this->coupons">
        <flux:table.columns>
            <flux:table.column>{{ __('Code') }}</flux:table.column>
            <flux:table.column>{{ __('Type') }}</flux:table.column>
            <flux:table.column>{{ __('Value') }}</flux:table.column>
            <flux:table.column>{{ __('Usage') }}</flux:table.column>
            <flux:table.column>{{ __('Window') }}</flux:table.column>
            <flux:table.column>{{ __('Status') }}</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->coupons as $coupon)
                <flux:table.row wire:key="coupon-{{ $coupon->id }}">
                    <flux:table.cell class="font-mono">{{ $coupon->code }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge :color="$coupon->type->color()" size="sm">{{ $coupon->type->label() }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        {{ $coupon->type === CouponType::Percentage ? number_format((float) $coupon->value, 0).'%' : number_format((float) $coupon->value, 2) }}
                    </flux:table.cell>
                    <flux:table.cell>{{ $coupon->used_count }} / {{ $coupon->usage_limit ?? '∞' }}</flux:table.cell>
                    <flux:table.cell>
                        @if ($coupon->starts_at || $coupon->expires_at)
                            {{ $coupon->starts_at?->format('d M Y') ?? '—' }} &ndash; {{ $coupon->expires_at?->format('d M Y') ?? '—' }}
                        @else
                            &mdash;
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:badge :color="$coupon->is_active ? 'green' : 'zinc'" size="sm">
                            {{ $coupon->is_active ? __('Active') : __('Inactive') }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex justify-end gap-2">
                            <flux:modal.trigger name="coupon-form">
                                <flux:button size="sm" wire:click="$dispatch('edit-coupon', { couponId: {{ $coupon->id }} })">
                                    {{ __('Edit') }}
                                </flux:button>
                            </flux:modal.trigger>

                            <flux:button size="sm" variant="ghost" wire:click="toggleActive({{ $coupon->id }})">
                                {{ $coupon->is_active ? __('Deactivate') : __('Activate') }}
                            </flux:button>
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="7">
                        <flux:text class="py-6 text-center">{{ __('No coupons yet.') }}</flux:text>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    <livewire:pages::admin.coupons.form-modal />
</div>
