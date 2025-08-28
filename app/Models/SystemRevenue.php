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
 * @property string $source_type
 * @property float $amount
 * @property string $description
 * @property int $user_subscription_id
 * @property int $user_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * == Relationships ==
 * @property-read User $user
 * @property-read UserSubscription $subscription
 */
class SystemRevenue extends Model
{
    use HasFactory;

    protected $fillable = [
        'source_type',
        'amount',
        'description',
        'user_subscription_id',
        'user_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    // ================================================================================
    // RELATIONSHIPS
    // ================================================================================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(UserSubscription::class, 'user_subscription_id');
    }

    // ================================================================================
    // QUERY SCOPES
    // ================================================================================

    public function scopeFromSubscriptions($query)
    {
        return $query->where('source_type', 'subscription');
    }

    // ================================================================================
    // STATIC METHODS
    // ================================================================================

    public static function recordSubscriptionRevenue(
        UserSubscription $subscription,
        float $amount,
        ?string $description = null
    ): self {
        return static::create([
            'source_type' => 'subscription',
            'amount' => $amount,
            'description' => $description ?? "Revenus souscription package {$subscription->package->display_name}",
            'user_subscription_id' => $subscription->id,
            'user_id' => $subscription->user_id,
        ]);
    }
}
