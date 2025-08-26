<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SubscriptionStatus;
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
 * @property-read \Illuminate\Database\Eloquent\Collection|WhatsAppAccountUsage[] $accountUsages
 */
class UserSubscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'package_id',
        'starts_at',
        'ends_at',
        'status',
        'messages_limit',
        'context_limit',
        'accounts_limit',
        'products_limit',
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

    public function accountUsages(): HasMany
    {
        return $this->hasMany(WhatsAppAccountUsage::class);
    }

    // ================================================================================
    // QUERY SCOPES
    // ================================================================================

    public function scopeActive($query)
    {
        return $query->where('status', SubscriptionStatus::ACTIVE()->value)->where('ends_at', '>', now());
    }

    public function scopeExpired($query)
    {
        return $query->where('ends_at', '<=', now())->orWhere('status', 'expired');
    }

    public function scopeCurrent($query)
    {
        return $query->where('starts_at', '<=', now())
            ->where('ends_at', '>', now())
            ->where('status', SubscriptionStatus::ACTIVE()->value);
    }

    // ================================================================================
    // STATUS METHODS
    // ================================================================================

    public function isActive(): bool
    {
        return $this->status === SubscriptionStatus::ACTIVE()->value &&
               $this->ends_at > now() &&
               $this->starts_at <= now();
    }

    public function isExpired(): bool
    {
        return $this->ends_at <= now() || $this->status === 'expired';
    }

    public function isCancelled(): bool
    {
        return $this->status === SubscriptionStatus::CANCELLED()->value;
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
        return (int) $this->starts_at->diffInDays($this->ends_at);
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
        return ! $this->user
            ->subscriptions()
            ->whereHas('package', fn ($q) => $q->where('name', 'trial'))
            ->exists();
    }

    public function isTrialSubscription(): bool
    {
        return $this->package->isTrial();
    }

    // ================================================================================
    // USAGE TRACKING
    // ================================================================================

    public function getTotalMessagesUsed(): int
    {
        return $this->accountUsages()->sum('messages_used');
    }

    public function getTotalOverageMessagesUsed(): int
    {
        return $this->accountUsages()->sum('overage_messages_used');
    }

    public function getRemainingMessages(): int
    {
        return max(0, $this->messages_limit - $this->getTotalMessagesUsed());
    }

    public function hasRemainingMessages(int $required = 1): bool
    {
        return $this->getRemainingMessages() >= $required;
    }

    /**
     * Check if we should send low quota alert
     */
    public function shouldSendLowQuotaAlert(): bool
    {
        $remainingMessages = $this->getRemainingMessages();
        $alertThreshold = config('whatsapp.billing.alert_threshold_percentage', 20);
        $thresholdMessages = ($this->messages_limit * $alertThreshold) / 100;

        return $remainingMessages <= $thresholdMessages;
    }

    public function getUsageForAccount(WhatsAppAccount $account): WhatsAppAccountUsage
    {
        return WhatsAppAccountUsage::getOrCreateForAccount($this, $account);
    }

    // ================================================================================
    // SUBSCRIPTION MANAGEMENT
    // ================================================================================

    public function cancel(?string $reason = null): void
    {
        $this->update([
            'status' => SubscriptionStatus::CANCELLED()->value,
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
        ]);
    }

    public function suspend(?string $reason = null): void
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
                'status' => SubscriptionStatus::ACTIVE()->value,
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

        if (! $trialPackage) {
            throw new \Exception('Trial package not found');
        }

        return static::create([
            'user_id' => $user->id,
            'package_id' => $trialPackage->id,
            'starts_at' => now(),
            'ends_at' => now()->addDays($trialPackage->duration_days ?? 7),
            'status' => SubscriptionStatus::ACTIVE()->value,
            'messages_limit' => $trialPackage->messages_limit,
            'context_limit' => $trialPackage->context_limit,
            'accounts_limit' => $trialPackage->accounts_limit,
            'products_limit' => $trialPackage->products_limit,
            'activated_at' => now(),
        ]);
    }
}
