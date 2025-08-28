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
 * @property int $user_id
 * @property int $whatsapp_account_id
 * @property int $whatsapp_conversation_id
 * @property int $whatsapp_message_id
 * @property string $ai_model
 * @property string $provider
 * @property int $prompt_tokens
 * @property int $completion_tokens
 * @property int $total_tokens
 * @property int $cached_tokens
 * @property float $prompt_cost_usd
 * @property float $completion_cost_usd
 * @property float $cached_cost_usd
 * @property float $total_cost_usd
 * @property float $total_cost_xaf
 * @property int $request_length
 * @property int $response_length
 * @property int $api_attempts
 * @property int $response_time_ms
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * == Relationships ==
 * @property-read User $user
 * @property-read WhatsAppAccount $whatsappAccount
 * @property-read WhatsAppConversation $conversation
 * @property-read WhatsAppMessage $message
 *
 * == Scopes ==
 *
 * @method static \Illuminate\Database\Eloquent\Builder|AiUsageLog byUser(int $userId)
 * @method static \Illuminate\Database\Eloquent\Builder|AiUsageLog byAccount(int $accountId)
 * @method static \Illuminate\Database\Eloquent\Builder|AiUsageLog byConversation(int $conversationId)
 * @method static \Illuminate\Database\Eloquent\Builder|AiUsageLog byDateRange($startDate, $endDate)
 * @method static \Illuminate\Database\Eloquent\Builder|AiUsageLog byProvider(string $provider)
 * @method static \Illuminate\Database\Eloquent\Builder|AiUsageLog byModel(string $model)
 */
final class AiUsageLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'whatsapp_account_id',
        'whatsapp_conversation_id',
        'whatsapp_message_id',
        'ai_model',
        'provider',
        'prompt_tokens',
        'completion_tokens',
        'total_tokens',
        'cached_tokens',
        'prompt_cost_usd',
        'completion_cost_usd',
        'cached_cost_usd',
        'total_cost_usd',
        'total_cost_xaf',
        'request_length',
        'response_length',
        'api_attempts',
        'response_time_ms',
    ];

    protected $casts = [
        'prompt_cost_usd' => 'decimal:6',
        'completion_cost_usd' => 'decimal:6',
        'cached_cost_usd' => 'decimal:6',
        'total_cost_usd' => 'decimal:6',
        'total_cost_xaf' => 'decimal:2',
    ];

    // ================================================================================
    // RELATIONSHIPS
    // ================================================================================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function whatsappAccount(): BelongsTo
    {
        return $this->belongsTo(WhatsAppAccount::class, 'whatsapp_account_id');
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(WhatsAppConversation::class, 'whatsapp_conversation_id');
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(WhatsAppMessage::class, 'whatsapp_message_id');
    }

    // ================================================================================
    // QUERY SCOPES
    // ================================================================================

    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByAccount($query, int $accountId)
    {
        return $query->where('whatsapp_account_id', $accountId);
    }

    public function scopeByConversation($query, int $conversationId)
    {
        return $query->where('whatsapp_conversation_id', $conversationId);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    public function scopeByProvider($query, string $provider)
    {
        return $query->where('provider', $provider);
    }

    public function scopeByModel($query, string $model)
    {
        return $query->where('ai_model', $model);
    }

    // ================================================================================
    // STATIC METHODS FOR REPORTING
    // ================================================================================

    public static function getTotalCostForUser(int $userId, $startDate = null, $endDate = null): float
    {
        $query = self::byUser($userId);

        if ($startDate && $endDate) {
            $query->byDateRange($startDate, $endDate);
        }

        return (float) $query->sum('total_cost_usd');
    }

    public static function getTotalCostForAccount(int $accountId, $startDate = null, $endDate = null): float
    {
        $query = self::byAccount($accountId);

        if ($startDate && $endDate) {
            $query->byDateRange($startDate, $endDate);
        }

        return (float) $query->sum('total_cost_usd');
    }

    public static function getTotalCostForConversation(int $conversationId): float
    {
        return (float) self::byConversation($conversationId)->sum('total_cost_usd');
    }

    public static function getTopUsers(int $limit = 10, $startDate = null, $endDate = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = self::selectRaw('user_id, SUM(total_cost_usd) as total_cost, COUNT(*) as request_count')
            ->with('user')
            ->groupBy('user_id')
            ->orderBy('total_cost', 'desc')
            ->limit($limit);

        if ($startDate && $endDate) {
            $query->byDateRange($startDate, $endDate);
        }

        return $query->get();
    }
}
