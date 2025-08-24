<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * == Properties ==
 *
 * @property int $id
 * @property int $user_id
 * @property int $package_id
 * @property Carbon $starts_at
 * @property Carbon $ends_at
 * @property Carbon|null $next_billing_date
 * @property string $status
 * @property float|null $amount_paid
 * @property string|null $payment_method
 * @property string|null $transaction_id
 * @property Carbon|null $activated_at
 * @property Carbon|null $cancelled_at
 * @property string|null $cancellation_reason
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * == Relationships ==
 * @property-read User $user
 * @property-read Package $package
 * @property-read \Illuminate\Database\Eloquent\Collection|UsageSubscriptionTracker[] $usageTrackers
 */
class UserSubscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'package_id',
        'starts_at',
        'ends_at',
        'next_billing_date',
        'status',
        'amount_paid',
        'payment_method',
        'transaction_id',
        'activated_at',
        'cancelled_at',
        'cancellation_reason',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'next_billing_date' => 'datetime',
        'activated_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'amount_paid' => 'decimal:2',
    ];

    // ================================================================================
    // RELATIONSHIPS
    // ================================================================================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function usageTrackers(): HasMany
    {
        return $this->hasMany(UsageSubscriptionTracker::class);
    }

    // ================================================================================
    // QUERY SCOPES
    // ================================================================================

    public function scopeActive($query)
    {
        return $query->where('status', 'active')->where('ends_at', '>', now());
    }

    public function scopeExpired($query)
    {
        return $query->where('ends_at', '<=', now())->orWhere('status', 'expired');
    }

    public function scopeCurrent($query)
    {
        return $query->where('starts_at', '<=', now())
                    ->where('ends_at', '>', now())
                    ->where('status', 'active');
    }

    // ================================================================================
    // STATUS METHODS
    // ================================================================================

    public function isActive(): bool
    {
        return $this->status === 'active' && 
               $this->ends_at > now() && 
               $this->starts_at <= now();
    }

    public function isExpired(): bool
    {
        return $this->ends_at <= now() || $this->status === 'expired';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    // ================================================================================
    // UTILITY METHODS
    // ================================================================================

    public function getRemainingDays(): int
    {
        if ($this->isExpired()) {
            return 0;
        }

        return max(0, $this->ends_at->diffInDays(now()));
    }

    public function getRemainingHours(): int
    {
        if ($this->isExpired()) {
            return 0;
        }

        return max(0, $this->ends_at->diffInHours(now()));
    }

    public function getDurationInDays(): int
    {
        return $this->starts_at->diffInDays($this->ends_at);
    }

    public function getUsagePercentage(): float
    {
        $totalDays = $this->getDurationInDays();
        $elapsedDays = $this->starts_at->diffInDays(now());

        if ($totalDays <= 0) {
            return 100.0;
        }

        return min(100.0, ($elapsedDays / $totalDays) * 100);
    }

    // ================================================================================
    // TRIAL METHODS
    // ================================================================================

    public function canSubscribeToTrial(): bool
    {
        // Vérifier si l'user a déjà eu un trial (incluant celui-ci)
        return !$this->user
            ->subscriptions()
            ->whereHas('package', fn($q) => $q->where('name', 'trial'))
            ->exists();
    }

    public function isTrialSubscription(): bool
    {
        return $this->package->isTrial();
    }

    // ================================================================================
    // USAGE TRACKING
    // ================================================================================

    public function getCurrentCycleTracker(): ?UsageSubscriptionTracker
    {
        $cycleStart = $this->starts_at->toDateString();
        
        return $this->usageTrackers()
            ->where('cycle_start_date', $cycleStart)
            ->first();
    }

    public function getOrCreateCurrentCycleTracker(): UsageSubscriptionTracker
    {
        $cycleStart = $this->starts_at->toDateString();
        $cycleEnd = $this->starts_at->copy()->addMonth()->toDateString();
        
        return UsageSubscriptionTracker::firstOrCreate(
            [
                'user_subscription_id' => $this->id,
                'cycle_start_date' => $cycleStart,
            ],
            [
                'cycle_end_date' => $cycleEnd,
                'messages_remaining' => $this->package->messages_limit,
                'last_reset_at' => now(),
            ]
        );
    }

    public function hasRemainingMessages(): bool
    {
        $tracker = $this->getCurrentCycleTracker();
        return $tracker ? $tracker->messages_remaining > 0 : true;
    }

    public function getRemainingMessages(): int
    {
        $tracker = $this->getCurrentCycleTracker();
        return $tracker ? $tracker->messages_remaining : $this->package->messages_limit;
    }

    // ================================================================================
    // SUBSCRIPTION MANAGEMENT
    // ================================================================================

    public function renew(?Carbon $newEndDate = null): void
    {
        if ($this->package->is_recurring) {
            $this->update([
                'ends_at' => $newEndDate ?? $this->ends_at->addMonth(),
                'next_billing_date' => $newEndDate ? $newEndDate->addMonth() : $this->next_billing_date?->addMonth(),
                'status' => 'active',
            ]);
        }
    }

    public function cancel(string $reason = null): void
    {
        $this->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
        ]);
    }

    public function suspend(string $reason = null): void
    {
        $this->update([
            'status' => 'suspended',
            'cancellation_reason' => $reason,
        ]);
    }

    public function reactivate(): void
    {
        if ($this->ends_at > now()) {
            $this->update([
                'status' => 'active',
                'cancellation_reason' => null,
            ]);
        }
    }

    // ================================================================================
    // STATIC METHODS
    // ================================================================================

    public static function createTrialForUser(User $user): self
    {
        $trialPackage = Package::getTrialPackage();
        
        if (!$trialPackage) {
            throw new \Exception('Trial package not found');
        }

        return static::create([
            'user_id' => $user->id,
            'package_id' => $trialPackage->id,
            'starts_at' => now(),
            'ends_at' => now()->addDays($trialPackage->duration_days ?? 7),
            'status' => 'active',
            'activated_at' => now(),
        ]);
    }
}