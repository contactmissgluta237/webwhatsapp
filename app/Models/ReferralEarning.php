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
 * @property int $referrer_id
 * @property int $referred_user_id
 * @property int $user_subscription_id
 * @property float $original_amount
 * @property float $commission_percentage
 * @property float $commission_amount
 * @property float $system_revenue
 * @property int $internal_transaction_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * == Relationships ==
 * @property-read User $referrer
 * @property-read User $referredUser
 * @property-read UserSubscription $subscription
 * @property-read InternalTransaction $transaction
 */
class ReferralEarning extends Model
{
    use HasFactory;

    protected $fillable = [
        'referrer_id',
        'referred_user_id',
        'user_subscription_id',
        'original_amount',
        'commission_percentage',
        'commission_amount',
        'system_revenue',
        'internal_transaction_id',
    ];

    protected $casts = [
        'original_amount' => 'decimal:2',
        'commission_percentage' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'system_revenue' => 'decimal:2',
    ];

    // ================================================================================
    // RELATIONSHIPS
    // ================================================================================

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referrer_id');
    }

    public function referredUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_user_id');
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(UserSubscription::class, 'user_subscription_id');
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(InternalTransaction::class, 'internal_transaction_id');
    }

    // ================================================================================
    // STATIC METHODS
    // ================================================================================

    public static function recordEarning(
        User $referrer,
        User $referredUser,
        UserSubscription $subscription,
        float $originalAmount,
        float $commissionPercentage,
        float $commissionAmount,
        InternalTransaction $transaction
    ): self {
        return static::create([
            'referrer_id' => $referrer->id,
            'referred_user_id' => $referredUser->id,
            'user_subscription_id' => $subscription->id,
            'original_amount' => $originalAmount,
            'commission_percentage' => $commissionPercentage,
            'commission_amount' => $commissionAmount,
            'system_revenue' => $originalAmount - $commissionAmount,
            'internal_transaction_id' => $transaction->id,
        ]);
    }
}
