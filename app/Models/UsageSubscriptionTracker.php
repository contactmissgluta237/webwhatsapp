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
 * @property int $user_subscription_id
 * @property int $month
 * @property int $year
 * @property int $messages_used
 * @property int $messages_remaining
 * @property int $base_messages_count
 * @property int $media_messages_count
 * @property int $accounts_linked
 * @property int $products_linked
 * @property Carbon|null $last_message_at
 * @property Carbon|null $last_reset_at
 * @property float $estimated_cost_xaf
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * == Relationships ==
 * @property-read UserSubscription $subscription
 */
class UsageSubscriptionTracker extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_subscription_id',
        'cycle_start_date',
        'cycle_end_date',
        'messages_used',
        'messages_remaining',
        'base_messages_count',
        'media_messages_count',
        'accounts_linked',
        'products_linked',
        'overage_messages_used',
        'overage_cost_paid_xaf',
        'last_message_at',
        'last_overage_payment_at',
        'last_reset_at',
        'estimated_cost_xaf',
    ];

    protected $casts = [
        'cycle_start_date' => 'date',
        'cycle_end_date' => 'date',
        'last_message_at' => 'datetime',
        'last_overage_payment_at' => 'datetime',
        'last_reset_at' => 'datetime',
        'estimated_cost_xaf' => 'decimal:2',
        'overage_cost_paid_xaf' => 'decimal:2',
    ];

    // ================================================================================
    // RELATIONSHIPS
    // ================================================================================

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(UserSubscription::class, 'user_subscription_id');
    }

    // ================================================================================
    // QUERY SCOPES
    // ================================================================================

    public function scopeCurrentCycle($query)
    {
        $today = now()->toDateString();

        return $query->where('cycle_start_date', '<=', $today)
            ->where('cycle_end_date', '>', $today);
    }

    public function scopeForSubscription($query, int $subscriptionId)
    {
        return $query->where('user_subscription_id', $subscriptionId);
    }

    // ================================================================================
    // USAGE TRACKING METHODS
    // ================================================================================

    public function incrementUsage(int $cost = 1): void
    {
        $this->increment('messages_used', $cost);
        $this->decrement('messages_remaining', $cost);
        $this->increment('base_messages_count');

        // Mise à jour du coût estimé
        $costInXAF = $cost * config('pricing.message_base_cost_xaf', 10);
        $this->increment('estimated_cost_xaf', $costInXAF);

        $this->update(['last_message_at' => now()]);
    }

    public function incrementMediaUsage(int $mediaCount): void
    {
        $this->increment('media_messages_count', $mediaCount);

        // Le coût des médias est déjà inclus dans incrementUsage
        // On met juste à jour les statistiques détaillées
    }

    public function updateAccountsLinked(int $count): void
    {
        $this->update(['accounts_linked' => $count]);
    }

    public function updateProductsLinked(int $count): void
    {
        $this->update(['products_linked' => $count]);
    }

    // ================================================================================
    // STATUS METHODS
    // ================================================================================

    public function hasRemainingMessages(): bool
    {
        return $this->messages_remaining > 0;
    }

    public function isLimitExceeded(): bool
    {
        return $this->messages_remaining <= 0;
    }

    public function canProcessMessage(int $messageCost): bool
    {
        // Peut traiter si il reste du quota OU si le wallet peut payer
        return $this->hasRemainingMessages() || $this->canAffordOverage($messageCost);
    }

    public function canAffordOverage(int $messageCost): bool
    {
        if (!config('pricing.overage.enabled', true)) {
            return false;
        }

        $user = $this->subscription->user;
        $wallet = $user->wallet;
        
        if (!$wallet) {
            return false;
        }

        $overageCost = $messageCost * config('pricing.overage.cost_per_message_xaf', 10);
        $minimumBalance = config('pricing.overage.minimum_wallet_balance', 0);
        
        return ($wallet->balance - $overageCost) >= $minimumBalance;
    }

    public function getUsagePercentage(): float
    {
        $limit = $this->subscription->package->messages_limit;

        if ($limit <= 0) {
            return 0.0;
        }

        return min(100.0, ($this->messages_used / $limit) * 100);
    }

    public function isNearLimit(int $threshold = 80): bool
    {
        return $this->getUsagePercentage() >= $threshold;
    }

    // ================================================================================
    // ANALYTICS METHODS
    // ================================================================================

    public function getTotalMessages(): int
    {
        return $this->messages_used;
    }

    public function getMediaToBaseRatio(): float
    {
        if ($this->base_messages_count <= 0) {
            return 0.0;
        }

        return $this->media_messages_count / $this->base_messages_count;
    }

    public function getAverageCostPerMessage(): float
    {
        if ($this->messages_used <= 0) {
            return 0.0;
        }

        return $this->estimated_cost_xaf / $this->messages_used;
    }

    public function getDailyAverage(): float
    {
        $cycleDays = $this->cycle_start_date->diffInDays($this->cycle_end_date);

        // Si on est dans le cycle actuel, calculer les jours écoulés
        if (now()->between($this->cycle_start_date, $this->cycle_end_date)) {
            // Utiliser startOfDay() pour éviter les calculs avec les heures
            $startDate = $this->cycle_start_date->copy()->startOfDay();
            $currentDate = now()->startOfDay();
            $elapsedDays = $startDate->diffInDays($currentDate) + 1; // Inclure le jour actuel

            return $elapsedDays > 0 ? $this->messages_used / $elapsedDays : 0.0;
        }

        // Sinon, utiliser le nombre total de jours du cycle
        return $cycleDays > 0 ? $this->messages_used / $cycleDays : 0.0;
    }

    public function getProjectedUsage(): int
    {
        $dailyAverage = $this->getDailyAverage();
        $cycleDays = $this->cycle_start_date->diffInDays($this->cycle_end_date);

        return (int) ceil($dailyAverage * $cycleDays);
    }

    // ================================================================================
    // RESET METHODS
    // ================================================================================

    public function resetForNewCycle(): void
    {
        $this->update([
            'messages_used' => 0,
            'messages_remaining' => $this->subscription->package->messages_limit,
            'base_messages_count' => 0,
            'media_messages_count' => 0,
            'estimated_cost_xaf' => 0,
            'last_reset_at' => now(),
        ]);
    }

    // ================================================================================
    // STATIC METHODS
    // ================================================================================

    public static function getCurrentCycleForSubscription(int $subscriptionId): ?self
    {
        return static::where('user_subscription_id', $subscriptionId)
            ->currentCycle()
            ->first();
    }

    public static function getOrCreateForSubscription(UserSubscription $subscription): self
    {
        $cycleStart = $subscription->starts_at->toDateString();
        $cycleEnd = $subscription->starts_at->copy()->addMonth()->toDateString();

        return static::firstOrCreate(
            [
                'user_subscription_id' => $subscription->id,
                'cycle_start_date' => $cycleStart,
            ],
            [
                'cycle_end_date' => $cycleEnd,
                'messages_remaining' => $subscription->package->messages_limit,
                'last_reset_at' => now(),
            ]
        );
    }

    public static function resetExpiredCycles(): int
    {
        $today = now()->toDateString();
        $expiredTrackers = static::where('cycle_end_date', '<=', $today)->get();
        $resetCount = 0;

        foreach ($expiredTrackers as $tracker) {
            // Créer un nouveau cycle pour cette souscription
            $subscription = $tracker->subscription;
            $newCycleStart = $tracker->cycle_end_date;
            $newCycleEnd = Carbon::parse($newCycleStart)->addMonth()->toDateString();

            static::create([
                'user_subscription_id' => $subscription->id,
                'cycle_start_date' => $newCycleStart,
                'cycle_end_date' => $newCycleEnd,
                'messages_remaining' => $subscription->package->messages_limit,
                'last_reset_at' => now(),
            ]);

            $resetCount++;
        }

        return $resetCount;
    }
}
