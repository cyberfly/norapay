<?php

use App\Concerns\BookingValidationRules;
use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Coupon;
use App\Models\Product;
use Flux\Flux;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    use BookingValidationRules;

    public Product $product;

    public string $customer_name = '';

    public string $customer_phone = '';

    public string $customer_email = '';

    public string $customer_address = '';

    public int $quantity = 1;

    public string $notes = '';

    public string $website = '';

    public string $couponCode = '';

    public ?string $discountAmount = null;

    public ?string $couponMessage = null;

    public ?string $couponMessageType = null;

    public bool $submitted = false;

    public ?string $bookingNumber = null;

    public function mount(Product $product): void
    {
        $this->product = $product;
    }

    #[Computed]
    public function subtotal(): string
    {
        return bcmul((string) $this->product->price, (string) max($this->quantity, 1), 2);
    }

    #[Computed]
    public function total(): string
    {
        $total = bcsub($this->subtotal, $this->discountAmount ?? '0.00', 2);

        return bccomp($total, '0.00', 2) === -1 ? '0.00' : $total;
    }

    public function updatedQuantity(): void
    {
        if ($this->discountAmount !== null) {
            $this->applyCoupon();
        }
    }

    public function applyCoupon(): void
    {
        $this->discountAmount = null;

        $code = strtoupper(trim($this->couponCode));

        if ($code === '') {
            $this->couponMessage = __('Sila masukkan kod kupon.');
            $this->couponMessageType = 'error';

            return;
        }

        $coupon = Coupon::query()->where('code', $code)->first();

        if (! $coupon || ! $coupon->isRedeemable()) {
            $this->couponMessage = __('Kod kupon tidak sah atau telah tamat tempoh.');
            $this->couponMessageType = 'error';

            return;
        }

        $this->discountAmount = $coupon->calculateDiscount($this->subtotal);
        $this->couponMessage = __('Kupon berjaya digunakan!');
        $this->couponMessageType = 'success';
    }

    public function placeBooking(): void
    {
        $validated = $this->validate($this->bookingRules());

        $couponCode = strtoupper(trim($this->couponCode));

        try {
            $booking = DB::transaction(function () use ($validated, $couponCode) {
                $coupon = null;
                $discount = '0.00';

                if ($couponCode !== '') {
                    $coupon = Coupon::query()->where('code', $couponCode)->lockForUpdate()->first();

                    if (! $coupon || ! $coupon->isRedeemable()) {
                        throw new RuntimeException('coupon-not-redeemable');
                    }

                    $subtotal = bcmul((string) $this->product->price, (string) $validated['quantity'], 2);
                    $discount = $coupon->calculateDiscount($subtotal);
                    $coupon->increment('used_count');
                }

                return Booking::create([
                    'product_id' => $this->product->id,
                    'product_name' => $this->product->name,
                    'unit_price' => $this->product->price,
                    'quantity' => $validated['quantity'],
                    'coupon_id' => $coupon?->id,
                    'coupon_code' => $coupon?->code,
                    'discount_amount' => $discount,
                    'customer_name' => $validated['customer_name'],
                    'customer_phone' => $validated['customer_phone'],
                    'customer_email' => $validated['customer_email'] ?: null,
                    'customer_address' => $validated['customer_address'] ?: null,
                    'notes' => $validated['notes'] ?: null,
                    'status' => BookingStatus::Pending,
                    'ip_address' => request()->ip(),
                    'user_agent' => substr((string) request()->userAgent(), 0, 255),
                ]);
            });
        } catch (RuntimeException $e) {
            if ($e->getMessage() !== 'coupon-not-redeemable') {
                throw $e;
            }

            $this->discountAmount = null;
            $this->addError('couponCode', __('Kod kupon tidak sah atau telah tamat tempoh.'));

            return;
        }

        $this->reset(['customer_name', 'customer_phone', 'customer_email', 'customer_address', 'quantity', 'notes', 'couponCode', 'discountAmount', 'couponMessage', 'couponMessageType']);
        $this->quantity = 1;
        $this->bookingNumber = $booking->booking_number;
        $this->submitted = true;

        Flux::toast(variant: 'success', text: __('Booking received. We will contact you shortly.'));
    }
}; ?>

<div>
    @if ($submitted)
        <div class="space-y-3 py-4 text-center">
            <span class="mx-auto flex size-12 items-center justify-center rounded-full bg-emerald-50 text-emerald-600">
                <flux:icon name="check" class="size-6" />
            </span>

            <flux:heading size="lg">{{ __('Terima kasih!') }}</flux:heading>
            <flux:text>{{ __('Tempahan anda telah diterima. Kami akan hubungi anda tidak lama lagi.') }}</flux:text>

            <div class="inline-flex items-center gap-2 rounded-full border border-dashed border-zinc-300 px-4 py-1.5">
                <span class="font-mono text-xs tracking-widest text-zinc-400 uppercase">{{ __('No. Tempahan') }}</span>
                <span class="font-mono text-sm font-semibold text-zinc-900">{{ $bookingNumber }}</span>
            </div>

            <div>
                <flux:button variant="ghost" wire:click="$set('submitted', false)" class="mt-2">
                    {{ __('Buat tempahan lain') }}
                </flux:button>
            </div>
        </div>
    @else
        <div class="mb-5">
            <span class="font-mono text-xs font-medium tracking-widest text-zinc-400 uppercase">{{ __('Isi butiran anda') }}</span>
            <flux:heading size="lg" class="mt-1">{{ __('Tempahan') }}</flux:heading>
        </div>

        <form wire:submit="placeBooking" class="space-y-4">
            <flux:input wire:model="customer_name" :label="__('Nama penuh')" required autocomplete="name" />

            <flux:input wire:model="customer_phone" :label="__('Nombor telefon')" type="tel" required autocomplete="tel" />

            <flux:input wire:model="customer_email" :label="__('Emel (pilihan)')" type="email" autocomplete="email" />

            <flux:textarea wire:model="customer_address" :label="__('Alamat penghantaran (pilihan)')" rows="3" />

            <flux:input wire:model.live="quantity" :label="__('Kuantiti')" type="number" min="1" required />

            <flux:textarea wire:model="notes" :label="__('Catatan (pilihan)')" rows="2" />

            <div class="flex items-end gap-2">
                <div class="flex-1">
                    <flux:input wire:model="couponCode" :label="__('Kod kupon (pilihan)')" placeholder="{{ __('cth: SAVE10') }}" />
                </div>

                <flux:button type="button" wire:click="applyCoupon">{{ __('Guna') }}</flux:button>
            </div>

            @error('couponCode')
                <flux:text size="sm" class="text-red-600">{{ $message }}</flux:text>
            @enderror

            @if ($couponMessage)
                <flux:text size="sm" class="{{ $couponMessageType === 'success' ? 'text-emerald-600' : 'text-red-600' }}">
                    {{ $couponMessage }}
                </flux:text>
            @endif

            <div class="space-y-1 rounded-lg bg-zinc-50 p-4 text-sm dark:bg-zinc-800">
                <div class="flex justify-between">
                    <span>{{ __('Subjumlah') }}</span>
                    <span>{{ $product->currency }} {{ number_format((float) $this->subtotal, 2) }}</span>
                </div>

                @if ($discountAmount)
                    <div class="flex justify-between text-emerald-600">
                        <span>{{ __('Diskaun') }}</span>
                        <span>-{{ $product->currency }} {{ number_format((float) $discountAmount, 2) }}</span>
                    </div>
                @endif

                <div class="flex justify-between font-semibold">
                    <span>{{ __('Jumlah') }}</span>
                    <span>{{ $product->currency }} {{ number_format((float) $this->total, 2) }}</span>
                </div>
            </div>

            <input type="text" wire:model="website" tabindex="-1" autocomplete="off" class="hidden" aria-hidden="true" />

            <flux:button variant="primary" type="submit" class="w-full">
                {{ __('Hantar tempahan') }}
            </flux:button>

            <flux:text size="sm" class="text-center text-zinc-400">
                {{ __('Tiada bayaran diperlukan sekarang — bayar semasa terima.') }}
            </flux:text>
        </form>
    @endif
</div>
