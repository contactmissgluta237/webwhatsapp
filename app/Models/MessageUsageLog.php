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
 * @property int $whatsapp_message_id
 * @property int|null $whatsapp_account_usage_id
 * @property int $whatsapp_conversation_id
 * @property int $user_id
 * @property float $ai_message_cost
 * @property int $product_messages_count
 * @property float $product_messages_cost
 * @property int $media_count
 * @property float $media_cost
 * @property float $total_cost
 * @property BillingType $billing_type
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * == Relationships ==
 * @property-read WhatsAppMessage $whatsappMessage
 * @property-read WhatsAppAccountUsage|null $whatsappAccountUsage
 * @property-read WhatsAppConversation $whatsappConversation
 * @property-read User $user
 *
 * == Scopes ==
 *
 * @method static \Illuminate\Database\Eloquent\Builder|MessageUsageLog byBillingType(BillingType $billingType)
 * @method static \Illuminate\Database\Eloquent\Builder|MessageUsageLog byConversation(int $conversationId)
 * @method static \Illuminate\Database\Eloquent\Builder|MessageUsageLog byUser(int $userId)
 */
class MessageUsageLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'whatsapp_message_id',
        'whatsapp_account_usage_id',
        'whatsapp_conversation_id',
        'user_id',
        'ai_message_cost',
        'product_messages_count',
        'product_messages_cost',
        'media_count',
        'media_cost',
        'total_cost',
        'billing_type',
    ];

    protected $casts = [
        'ai_message_cost' => 'decimal:2',
        'product_messages_cost' => 'decimal:2',
        'media_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'billing_type' => BillingType::class,
    ];

    /**
     * Get the WhatsApp message that this usage log belongs to.
     */
    public function whatsappMessage(): BelongsTo
    {
        return $this->belongsTo(WhatsAppMessage::class, 'whatsapp_message_id');
    }

    /**
     * Get the WhatsApp account usage that this log belongs to (nullable for wallet-direct billing).
     */
    public function whatsappAccountUsage(): BelongsTo
    {
        return $this->belongsTo(WhatsAppAccountUsage::class, 'whatsapp_account_usage_id');
    }

    /**
     * Get the WhatsApp conversation that this usage log belongs to.
     */
    public function whatsappConversation(): BelongsTo
    {
        return $this->belongsTo(WhatsAppConversation::class, 'whatsapp_conversation_id');
    }

    /**
     * Get the user that incurred this usage cost.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ================================================================================
    // PUBLIC METHODS
    // ================================================================================

    /**
     * Check if this usage was billed from subscription quota.
     */
    public function isFromSubscriptionQuota(): bool
    {
        return $this->billing_type === BillingType::SUBSCRIPTION_QUOTA;
    }

    /**
     * Check if this usage was billed directly from wallet.
     */
    public function isWalletDirect(): bool
    {
        return $this->billing_type === BillingType::WALLET_DIRECT;
    }

    /**
     * Check if this usage includes product messages.
     */
    public function hasProducts(): bool
    {
        return $this->product_messages_count > 0;
    }

    /**
     * Check if this usage includes media.
     */
    public function hasMedia(): bool
    {
        return $this->media_count > 0;
    }

    // ================================================================================
    // SCOPES
    // ================================================================================

    /**
     * Scope a query to only include usage logs of a specific billing type.
     */
    public function scopeByBillingType($query, BillingType $billingType)
    {
        return $query->where('billing_type', $billingType);
    }

    /**
     * Scope a query to only include usage logs for a specific conversation.
     */
    public function scopeByConversation($query, int $conversationId)
    {
        return $query->where('whatsapp_conversation_id', $conversationId);
    }

    /**
     * Scope a query to only include usage logs for a specific user.
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}
