<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CouponStatus;
use App\Enums\CouponType;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * == Properties ==
 *
 * @property int $id
 * @property string $code
 * @property CouponType $type
 * @property float $value
 * @property CouponStatus $status
 * @property int $usage_limit
 * @property int $used_count
 * @property Carbon|null $valid_from
 * @property Carbon|null $valid_until
 * @property int $created_by
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * == Relationships ==
 * @property-read User $creator
 * @property-read Collection|CouponUsage[] $usages
 */
class Coupon extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'type',
        'value',
        'status',
        'usage_limit',
        'used_count',
        'valid_from',
        'valid_until',
        'created_by',
    ];

    protected $casts = [
        'type' => CouponType::class,
        'status' => CouponStatus::class,
        'value' => 'decimal:2',
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
    ];

    // ================================================================================
    // RELATIONSHIPS
    // ================================================================================

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function usages(): HasMany
    {
        return $this->hasMany(CouponUsage::class);
    }

    // ================================================================================
    // QUERY SCOPES
    // ================================================================================

    public function scopeActive($query)
    {
        return $query->where('status', CouponStatus::ACTIVE());
    }

    public function scopeValid($query)
    {
        $now = now();

        return $query->where('status', CouponStatus::ACTIVE())
            ->where(function ($q) use ($now) {
                $q->whereNull('valid_from')->orWhere('valid_from', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('valid_until')->orWhere('valid_until', '>=', $now);
            });
    }

    // ================================================================================
    // VALIDATION METHODS
    // ================================================================================

    public function isValid(): bool
    {
        if ($this->status !== CouponStatus::ACTIVE()) {
            return false;
        }

        $now = now();

        if ($this->valid_from && $now < $this->valid_from) {
            return false;
        }

        if ($this->valid_until && $now > $this->valid_until) {
            return false;
        }

        return $this->used_count < $this->usage_limit;
    }

    public function canBeUsed(): bool
    {
        return $this->isValid() && $this->used_count < $this->usage_limit;
    }

    public function isExpired(): bool
    {
        if ($this->status === CouponStatus::EXPIRED()) {
            return true;
        }

        if ($this->valid_until && now() > $this->valid_until) {
            return true;
        }

        return $this->used_count >= $this->usage_limit;
    }

    // ================================================================================
    // BUSINESS METHODS
    // ================================================================================

    public function calculateDiscount(float $amount): float
    {
        if ($this->type === CouponType::PERCENTAGE()) {
            return ($amount * $this->value) / 100;
        }

        return min($this->value, $amount);
    }

    public function applyDiscount(float $amount): float
    {
        $discount = $this->calculateDiscount($amount);

        return max(0, $amount - $discount);
    }

    public function markAsUsed(): void
    {
        $this->increment('used_count');

        if ($this->used_count >= $this->usage_limit) {
            $this->update(['status' => CouponStatus::USED()]);
        }
    }

    public function markAsExpired(): void
    {
        $this->update(['status' => CouponStatus::EXPIRED()]);
    }

    // ================================================================================
    // STATIC METHODS
    // ================================================================================

    public static function findByCode(string $code): ?self
    {
        return static::where('code', strtoupper($code))->first();
    }

    public static function generateUniqueCode(int $length = 8): string
    {
        do {
            $code = strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, $length));
        } while (static::where('code', $code)->exists());

        return $code;
    }
}
