<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Usage tracking per WhatsApp account within a subscription
 *
 * == Properties ==
 *
 * @property int $id
 * @property int $user_subscription_id
 * @property int $whats_app_account_id
 * @property int $messages_used
 * @property int $base_messages_count
 * @property int $media_messages_count
 * @property int $overage_messages_used
 * @property float $overage_cost_paid_xaf
 * @property Carbon|null $last_message_at
 * @property Carbon|null $last_overage_payment_at
 * @property float $estimated_cost_xaf
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * == Relationships ==
 * @property-read UserSubscription $subscription
 * @property-read WhatsAppAccount $whatsAppAccount
 */
class WhatsAppAccountUsage extends Model
{
    protected $table = 'whatsapp_account_usages';
    use HasFactory;

    protected $fillable = [
        'user_subscription_id',
        'whatsapp_account_id',
        'messages_used',
        'base_messages_count',
        'media_messages_count',
        'overage_messages_used',
        'overage_cost_paid_xaf',
        'last_message_at',
        'last_overage_payment_at',
        'estimated_cost_xaf',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
        'last_overage_payment_at' => 'datetime',
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

    public function whatsAppAccount(): BelongsTo
    {
        return $this->belongsTo(WhatsAppAccount::class, 'whatsapp_account_id');
    }

    // ================================================================================
    // USAGE TRACKING METHODS
    // ================================================================================

    public function incrementUsage(int $cost = 1): void
    {
        $this->increment('messages_used', $cost);
        $this->increment('base_messages_count');

        $costInXAF = $cost * config('pricing.message_base_cost_xaf', 10);
        $this->increment('estimated_cost_xaf', $costInXAF);

        $this->update(['last_message_at' => now()]);
    }

    public function incrementOverageUsage(int $cost, float $paidAmount): void
    {
        $this->increment('overage_messages_used', $cost);
        $this->increment('overage_cost_paid_xaf', $paidAmount);

        $this->update([
            'last_message_at' => now(),
            'last_overage_payment_at' => now(),
        ]);
    }

    public function incrementMediaUsage(int $mediaCount): void
    {
        $this->increment('media_messages_count', $mediaCount);
    }

    /**
     * Can this user afford the wallet debit for overage billing
     */
    public function canAffordWalletDebit(float $amount): bool
    {
        $user = $this->subscription->user;
        $wallet = $user->wallet;

        if (! $wallet) {
            return false;
        }

        return $wallet->balance >= $amount;
    }

    /**
     * Debit wallet for overage billing with protection
     */
    public function debitWalletForOverage(float $amount): bool
    {
        if (! $this->canAffordWalletDebit($amount)) {
            return false;
        }

        $user = $this->subscription->user;
        $wallet = $user->wallet;

        // Update wallet balance (protected against negative)
        $newBalance = max(0, $wallet->balance - $amount);
        $wallet->update(['balance' => $newBalance]);

        // Track overage payment
        $this->increment('overage_cost_paid_xaf', $amount);
        $this->update(['last_overage_payment_at' => now()]);

        Log::info('[WhatsAppAccountUsage] Wallet debited for overage', [
            'account_usage_id' => $this->id,
            'amount_debited' => $amount,
            'new_wallet_balance' => $newBalance,
        ]);

        return true;
    }

    // ================================================================================
    // STATUS METHODS
    // ================================================================================

    public function canAffordOverage(int $messageCost): bool
    {
        if (! config('pricing.overage.enabled', true)) {
            return false;
        }

        $user = $this->subscription->user;
        $wallet = $user->wallet;

        if (! $wallet) {
            return false;
        }

        $overageCost = $messageCost * config('pricing.overage.cost_per_message_xaf', 10);
        $minimumBalance = config('pricing.overage.minimum_wallet_balance', 0);

        return ($wallet->balance - $overageCost) >= $minimumBalance;
    }

    // ================================================================================
    // ANALYTICS METHODS
    // ================================================================================

    public function getTotalMessages(): int
    {
        return $this->messages_used + $this->overage_messages_used;
    }

    public function getMediaToBaseRatio(): float
    {
        if ($this->base_messages_count <= 0) {
            return 0.0;
        }

        return $this->media_messages_count / $this->base_messages_count;
    }

    public function getTotalCostPaid(): float
    {
        return $this->estimated_cost_xaf + $this->overage_cost_paid_xaf;
    }

    // ================================================================================
    // STATIC METHODS
    // ================================================================================

    public static function getOrCreateForAccount(UserSubscription $subscription, WhatsAppAccount $account): self
    {
        return static::firstOrCreate(
            [
                'user_subscription_id' => $subscription->id,
                'whatsapp_account_id' => $account->id,
            ],
            [
                'messages_used' => 0,
                'estimated_cost_xaf' => 0,
            ]
        );
    }
}
