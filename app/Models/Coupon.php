<?php

namespace App\Models;

use App\Enums\CouponType;
use Database\Factories\CouponFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $code
 * @property CouponType $type
 * @property string $value
 * @property Carbon|null $starts_at
 * @property Carbon|null $expires_at
 * @property int|null $usage_limit
 * @property int $used_count
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['code', 'type', 'value', 'starts_at', 'expires_at', 'usage_limit', 'is_active'])]
class Coupon extends Model
{
    /** @use HasFactory<CouponFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => CouponType::class,
            'value' => 'decimal:2',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'usage_limit' => 'integer',
            'used_count' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Coupon $coupon): void {
            $coupon->code = strtoupper($coupon->code);
        });
    }

    /**
     * @param  Builder<Coupon>  $query
     * @return Builder<Coupon>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function isRedeemable(): bool
    {
        $now = now();

        return $this->is_active
            && ($this->starts_at === null || $this->starts_at->lte($now))
            && ($this->expires_at === null || $this->expires_at->gte($now))
            && ($this->usage_limit === null || $this->used_count < $this->usage_limit);
    }

    public function calculateDiscount(string $subtotal): string
    {
        $discount = match ($this->type) {
            CouponType::Percentage => bcmul($subtotal, bcdiv((string) $this->value, '100', 4), 2),
            CouponType::Fixed => (string) $this->value,
        };

        return bccomp($discount, $subtotal, 2) === 1 ? $subtotal : $discount;
    }
}
