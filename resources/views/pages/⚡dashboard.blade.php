<?php

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Product;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

new class extends Component
{
    #[Url]
    public int $period = 30;

    public function mount(): void
    {
        Gate::authorize('access-admin');
    }

    /**
     * @return array{revenue: string, orders: int, pendingOrders: int, activeProducts: int}
     */
    #[Computed]
    public function stats(): array
    {
        return [
            'revenue' => (string) Booking::query()
                ->whereIn('status', [BookingStatus::Confirmed, BookingStatus::Completed])
                ->sum('total_price'),
            'orders' => Booking::query()->count(),
            'pendingOrders' => Booking::query()->where('status', BookingStatus::Pending)->count(),
            'activeProducts' => Product::query()->active()->count(),
        ];
    }

    /**
     * @return array{labels: array<int, string>, data: array<int, float>}
     */
    #[Computed]
    public function salesChart(): array
    {
        $startDate = now()->subDays($this->period - 1)->startOfDay();

        $sales = Booking::query()
            ->whereIn('status', [BookingStatus::Confirmed, BookingStatus::Completed])
            ->where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as date, SUM(total_price) as total')
            ->groupBy('date')
            ->pluck('total', 'date');

        $labels = [];
        $data = [];

        for ($date = $startDate; $date->lte(now()); $date = $date->addDay()) {
            $labels[] = $date->format('d M');
            $data[] = (float) ($sales[$date->format('Y-m-d')] ?? 0);
        }

        return ['labels' => $labels, 'data' => $data];
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
    <div class="grid auto-rows-min gap-4 md:grid-cols-3">
        <div class="rounded-xl border border-neutral-200 p-4 dark:border-neutral-700">
            <flux:text>{{ __('Revenue') }}</flux:text>
            <flux:heading size="xl" class="mt-1">{{ number_format((float) $this->stats['revenue'], 2) }}</flux:heading>
            <flux:text size="sm" class="mt-1 text-zinc-500 dark:text-zinc-400">{{ __('Confirmed & completed orders') }}</flux:text>
        </div>

        <div class="rounded-xl border border-neutral-200 p-4 dark:border-neutral-700">
            <flux:text>{{ __('Orders') }}</flux:text>
            <flux:heading size="xl" class="mt-1">{{ $this->stats['orders'] }}</flux:heading>
            <flux:text size="sm" class="mt-1 text-zinc-500 dark:text-zinc-400">{{ __(':count pending', ['count' => $this->stats['pendingOrders']]) }}</flux:text>
        </div>

        <div class="rounded-xl border border-neutral-200 p-4 dark:border-neutral-700">
            <flux:text>{{ __('Active products') }}</flux:text>
            <flux:heading size="xl" class="mt-1">{{ $this->stats['activeProducts'] }}</flux:heading>
        </div>
    </div>

    <div class="relative flex h-full flex-1 flex-col gap-4 overflow-hidden rounded-xl border border-neutral-200 p-4 dark:border-neutral-700">
        <div class="flex items-center justify-between">
            <flux:heading size="lg">{{ __('Sales') }}</flux:heading>

            <flux:select wire:model.live="period" class="max-w-40">
                <flux:select.option value="7">{{ __('Last 7 days') }}</flux:select.option>
                <flux:select.option value="30">{{ __('Last 30 days') }}</flux:select.option>
                <flux:select.option value="90">{{ __('Last 90 days') }}</flux:select.option>
            </flux:select>
        </div>

        <div
            wire:key="sales-chart-{{ $this->period }}"
            wire:ignore
            x-data="salesChart(@js($this->salesChart['labels']), @js($this->salesChart['data']))"
            class="h-80 flex-1"
        >
            <canvas x-ref="canvas"></canvas>
        </div>
    </div>
</div>

@vite('resources/js/sales-chart.js')
