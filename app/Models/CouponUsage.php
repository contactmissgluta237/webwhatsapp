<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * == Properties ==
 *
 * @property int $id
 * @property int $coupon_id
 * @property int $user_id
 * @property int $user_subscription_id
 * @property float $original_price
 * @property float $discount_amount
 * @property float $final_price
 * @property Carbon $used_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * == Relationships ==
 * @property-read Coupon $coupon
 * @property-read User $user
 * @property-read UserSubscription $subscription
 */
class CouponUsage extends Model
{
    use HasFactory;

    protected $fillable = [
        'coupon_id',
        'user_id',
        'user_subscription_id',
        'original_price',
        'discount_amount',
        'final_price',
        'used_at',
    ];

    protected $casts = [
        'original_price' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'final_price' => 'decimal:2',
        'used_at' => 'datetime',
    ];

    // ================================================================================
    // RELATIONSHIPS
    // ================================================================================

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(UserSubscription::class, 'user_subscription_id');
    }

    // ================================================================================
    // STATIC METHODS
    // ================================================================================

    public static function recordUsage(
        Coupon $coupon,
        User $user,
        UserSubscription $subscription,
        float $originalPrice,
        float $discountAmount,
        float $finalPrice
    ): self {
        return static::create([
            'coupon_id' => $coupon->id,
            'user_id' => $user->id,
            'user_subscription_id' => $subscription->id,
            'original_price' => $originalPrice,
            'discount_amount' => $discountAmount,
            'final_price' => $finalPrice,
            'used_at' => now(),
        ]);
    }
}
