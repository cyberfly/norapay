<?php

use App\Enums\CouponType;
use App\Models\Coupon;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    public ?int $editingId = null;

    public string $code = '';

    public string $type = CouponType::Fixed->value;

    public string $value = '';

    public string $starts_at = '';

    public string $expires_at = '';

    public string $usage_limit = '';

    public bool $is_active = true;

    #[On('create-coupon')]
    public function openForCreate(): void
    {
        $this->reset(['editingId', 'code', 'value', 'starts_at', 'expires_at', 'usage_limit']);
        $this->type = CouponType::Fixed->value;
        $this->is_active = true;
    }

    #[On('edit-coupon')]
    public function openForEdit(int $couponId): void
    {
        $coupon = Coupon::findOrFail($couponId);

        $this->editingId = $coupon->id;
        $this->code = $coupon->code;
        $this->type = $coupon->type->value;
        $this->value = (string) $coupon->value;
        $this->starts_at = $coupon->starts_at?->format('Y-m-d\TH:i') ?? '';
        $this->expires_at = $coupon->expires_at?->format('Y-m-d\TH:i') ?? '';
        $this->usage_limit = $coupon->usage_limit !== null ? (string) $coupon->usage_limit : '';
        $this->is_active = $coupon->is_active;
    }

    public function save(): void
    {
        $this->code = strtoupper(trim($this->code));

        $validated = $this->validate([
            'code' => ['required', 'string', 'max:50', 'alpha_dash', Rule::unique('coupons', 'code')->ignore($this->editingId)],
            'type' => ['required', Rule::enum(CouponType::class)],
            'value' => [
                'required',
                'numeric',
                'min:0.01',
                $this->type === CouponType::Percentage->value ? 'max:100' : 'max:999999.99',
            ],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['boolean'],
        ]);

        $validated['starts_at'] = $validated['starts_at'] ?: null;
        $validated['expires_at'] = $validated['expires_at'] ?: null;
        $validated['usage_limit'] = $validated['usage_limit'] ?: null;

        Coupon::updateOrCreate(['id' => $this->editingId], $validated);

        $this->dispatch('coupon-saved');

        Flux::modal('coupon-form')->close();
        Flux::toast(variant: 'success', text: __('Coupon saved.'));
    }
}; ?>

<flux:modal name="coupon-form" class="w-full max-w-lg">
    <form wire:submit="save" class="space-y-6">
        <flux:heading size="lg">{{ $editingId ? __('Edit coupon') : __('New coupon') }}</flux:heading>

        <flux:input wire:model="code" :label="__('Code')" placeholder="SAVE10" required />

        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            <flux:select wire:model="type" :label="__('Type')">
                @foreach (CouponType::cases() as $case)
                    <flux:select.option value="{{ $case->value }}">{{ $case->label() }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:input wire:model="value" :label="__('Value')" type="number" step="0.01" min="0" required />
        </div>

        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            <flux:input wire:model="starts_at" :label="__('Starts at (optional)')" type="datetime-local" />

            <flux:input wire:model="expires_at" :label="__('Expires at (optional)')" type="datetime-local" />
        </div>

        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            <flux:input wire:model="usage_limit" :label="__('Usage limit (optional)')" type="number" min="1" />

            <flux:switch wire:model="is_active" :label="__('Active')" />
        </div>

        <div class="flex justify-end space-x-2 rtl:space-x-reverse">
            <flux:modal.close>
                <flux:button variant="filled">{{ __('Cancel') }}</flux:button>
            </flux:modal.close>

            <flux:button variant="primary" type="submit">
                {{ __('Save') }}
            </flux:button>
        </div>
    </form>
</flux:modal>
