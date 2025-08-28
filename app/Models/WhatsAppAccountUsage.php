<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BillingType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * == Properties ==
 *
 * @property int $id
 * @property int|null $user_subscription_id
 * @property int $whatsapp_account_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * == Accessors ==
 * @property-read int $messages_used
 * @property-read float $overage_cost_paid_xaf
 * @property-read int $media_messages_count
 * @property-read int $overage_messages_used
 * @property-read Carbon|null $last_message_at
 * @property-read Carbon|null $last_overage_payment_at
 * @property-read float $estimated_cost_xaf
 *
 * == Relationships ==
 * @property-read UserSubscription|null $subscription
 * @property-read WhatsAppAccount $whatsAppAccount
 * @property-read \Illuminate\Database\Eloquent\Collection|MessageUsageLog[] $messageUsageLogs
 */
class WhatsAppAccountUsage extends Model
{
    protected $table = 'whatsapp_account_usages';
    use HasFactory;

    protected $fillable = [
        'user_subscription_id',
        'whatsapp_account_id',
    ];

    // ================================================================================
    // RELATIONSHIPS
    // ================================================================================

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(UserSubscription::class, 'user_subscription_id');
    }

    public function whatsAppAccount(): BelongsTo
    {
        return $this->belongsTo(WhatsAppAccount::class, 'whatsapp_account_id');
    }

    public function messageUsageLogs(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(MessageUsageLog::class, 'whatsapp_account_usage_id');
    }

    // ================================================================================
    // PUBLIC METHODS
    // ================================================================================

    /**
     * Get the number of messages used from subscription quota.
     */
    public function getMessagesUsedAttribute(): int
    {
        return $this->messageUsageLogs()
            ->where('billing_type', BillingType::SUBSCRIPTION_QUOTA)
            ->count();
    }

    /**
     * Get the total cost paid from wallet in XAF.
     */
    public function getOverageCostPaidXafAttribute(): float
    {
        return (float) $this->messageUsageLogs()
            ->where('billing_type', BillingType::WALLET_DIRECT)
            ->sum('total_cost');
    }

    /**
     * Get the total count of media messages.
     */
    public function getMediaMessagesCountAttribute(): int
    {
        return $this->messageUsageLogs()->sum('media_count');
    }

    /**
     * Get the number of messages billed directly to wallet.
     */
    public function getOverageMessagesUsedAttribute(): int
    {
        return $this->messageUsageLogs()
            ->where('billing_type', BillingType::WALLET_DIRECT)
            ->count();
    }

    /**
     * Get the timestamp of the last message.
     */
    public function getLastMessageAtAttribute(): ?Carbon
    {
        /** @var MessageUsageLog|null */
        $lastLog = $this->messageUsageLogs()
            ->orderBy('created_at', 'desc')
            ->first();

        return $lastLog ? $lastLog->created_at : null;
    }

    /**
     * Get the timestamp of the last wallet payment.
     */
    public function getLastOveragePaymentAtAttribute(): ?Carbon
    {
        /** @var MessageUsageLog|null */
        $lastOverageLog = $this->messageUsageLogs()
            ->where('billing_type', BillingType::WALLET_DIRECT)
            ->orderBy('created_at', 'desc')
            ->first();

        return $lastOverageLog?->created_at;
    }

    /**
     * Get the total estimated cost in XAF.
     */
    public function getEstimatedCostXafAttribute(): float
    {
        return (float) $this->messageUsageLogs()->sum('total_cost');
    }

    /**
     * Check if this usage has a subscription.
     */
    public function hasSubscription(): bool
    {
        return $this->user_subscription_id !== null;
    }

    /**
     * Check if this usage is wallet-only.
     */
    public function isWalletOnly(): bool
    {
        return $this->user_subscription_id === null;
    }

    /**
     * Get the ratio of media messages to total messages.
     */
    public function getMediaToBaseRatio(): float
    {
        $totalMessages = $this->messageUsageLogs()->count();

        if ($totalMessages <= 0) {
            return 0.0;
        }

        return $this->media_messages_count / $totalMessages;
    }

    /**
     * Get the total cost paid.
     */
    public function getTotalCostPaid(): float
    {
        return $this->estimated_cost_xaf;
    }

    /**
     * Get or create usage record for a subscription account.
     */
    public static function getOrCreateForAccount(UserSubscription $subscription, WhatsAppAccount $account): self
    {
        return static::firstOrCreate([
            'user_subscription_id' => $subscription->id,
            'whatsapp_account_id' => $account->id,
        ]);
    }

    /**
     * Get or create usage record for a wallet-only account.
     */
    public static function getOrCreateWalletOnlyUsage(WhatsAppAccount $account): self
    {
        return static::firstOrCreate([
            'user_subscription_id' => null,
            'whatsapp_account_id' => $account->id,
        ]);
    }
}
