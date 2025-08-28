<?php

declare(strict_types=1);

namespace App\Models;

use App\Casts\SpatieEnumCast;
use App\Enums\MessageDirection;
use App\Enums\WhatsAppStatus;
use App\Traits\HasMediaCollections;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Spatie\MediaLibrary\HasMedia;

/**
 * == Properties ==
 *
 * @property int $id
 * @property int $user_id
 * @property string $session_name
 * @property string $session_id
 * @property string|null $phone_number
 * @property WhatsAppStatus $status
 * @property string|null $qr_code
 * @property Carbon|null $last_seen_at
 * @property array|null $session_data
 * @property string|null $agent_name
 * @property bool $agent_enabled
 * @property int|null $ai_model_id
 * @property string $response_time
 * @property string|null $agent_prompt
 * @property array|null $trigger_words
 * @property bool $stop_on_human_reply
 * @property string|null $contextual_information
 * @property array|null $ignore_words
 * @property Carbon|null $last_ai_response_at
 * @property int $daily_ai_responses
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * == Accessors ==
 * @property-read string $session_name_with_user
 *
 * == Relationships ==
 * @property-read User $user
 * @property-read Collection|WhatsAppConversation[] $conversations
 * @property-read AiContext|null $aiContext
 * @property-read AiModel|null $aiModel
 * @property-read Collection|UserProduct[] $userProducts
 * @property-read Collection|AiUsageLog[] $aiUsageLogs
 */
final class WhatsAppAccount extends Model implements HasMedia
{
    use HasFactory;
    use HasMediaCollections;

    protected $table = 'whatsapp_accounts';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'session_name',
        'session_id',
        'phone_number',
        'status',
        'qr_code',
        'last_seen_at',
        'session_data',
        'agent_name',
        'agent_enabled',
        'ai_model_id',
        'response_time',
        'agent_prompt',
        'trigger_words',
        'stop_on_human_reply',
        'contextual_information',
        'ignore_words',
        'last_ai_response_at',
        'daily_ai_responses',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string|class-string>
     */
    protected $casts = [
        'status' => SpatieEnumCast::class.':'.WhatsAppStatus::class,
        'last_seen_at' => 'datetime',
        'session_data' => 'array',
        'agent_enabled' => 'boolean',
        'stop_on_human_reply' => 'boolean',
        'trigger_words' => 'array',
        'ignore_words' => 'array',
        'last_ai_response_at' => 'datetime',
        'daily_ai_responses' => 'integer',
    ];

    // ================================================================================
    // RELATIONSHIPS
    // ================================================================================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(WhatsAppConversation::class, 'whatsapp_account_id');
    }

    public function aiContext(): HasOne
    {
        return $this->hasOne(AiContext::class);
    }

    public function aiModel(): BelongsTo
    {
        return $this->belongsTo(AiModel::class);
    }

    public function userProducts(): BelongsToMany
    {
        return $this->belongsToMany(
            UserProduct::class,
            'whatsapp_account_products',
            'whatsapp_account_id',
            'user_product_id'
        );
    }

    public function aiUsageLogs(): HasMany
    {
        return $this->hasMany(AiUsageLog::class, 'whatsapp_account_id');
    }

    public function getAiModel()
    {
        return $this->ai_model_id ? AiModel::find($this->ai_model_id) : null;
    }

    public function getSessionNameWithUserAttribute(): string
    {
        return $this->session_name.' ('.$this->user->full_name.')';
    }

    // MEDIA COLLECTIONS
    // ================================================================================

    public function requiresMainImage(): bool
    {
        return false;
    }

    public function supportsMultipleImages(): bool
    {
        return true;
    }

    public function getImageIdentifier(): string
    {
        return "whatsapp_account_{$this->id}";
    }

    // ================================================================================
    // PUBLIC METHODS
    // ================================================================================

    public function isConnected(): bool
    {
        return $this->status->equals(WhatsAppStatus::CONNECTED());
    }

    public function isDisconnected(): bool
    {
        return $this->status->equals(WhatsAppStatus::DISCONNECTED());
    }

    public function isConnecting(): bool
    {
        return $this->status->equals(WhatsAppStatus::CONNECTING());
    }

    public function hasQrCode(): bool
    {
        return ! empty($this->qr_code);
    }

    public function getTotalConversations(): int
    {
        return $this->conversations()->count();
    }

    public function getActiveConversations(): int
    {
        return $this->conversations()
            ->whereNotNull('last_message_at')
            ->where('last_message_at', '>=', now()->subDays(7))
            ->count();
    }

    public function getTodayMessagesCount(): array
    {
        /** @var \Illuminate\Database\Eloquent\Collection<\App\Models\WhatsAppConversation> $conversations */
        $conversations = $this->conversations()->with(['messages' => function (Builder $query): void {
            $query->whereDate('created_at', today());
        }])->get();

        $inbound = 0;
        $outbound = 0;

        foreach ($conversations as $conversation) {
            /** @var \App\Models\WhatsAppMessage $message */
            foreach ($conversation->messages as $message) {
                if ($message->direction === MessageDirection::INBOUND()->value) {
                    $inbound++;
                } else {
                    $outbound++;
                }
            }
        }

        return [
            'inbound' => $inbound,
            'outbound' => $outbound,
            'total' => $inbound + $outbound,
        ];
    }

    // ================================================================================
    // AI AGENT METHODS
    // ================================================================================

    public function hasAiAgent(): bool
    {
        return $this->agent_enabled && $this->ai_model_id !== null;
    }

    public function enableAiAgent(int $aiModelId, ?string $prompt = null, ?string $triggerWords = null, ?string $responseTime = null): void
    {
        $this->update([
            'agent_enabled' => true,
            'ai_model_id' => $aiModelId,
            'agent_prompt' => $prompt,
            'trigger_words' => $triggerWords,
            'response_time' => $responseTime ?? 'random',
        ]);
    }

    public function disableAiAgent(): void
    {
        $this->update([
            'agent_enabled' => false,
        ]);
    }

    public function shouldTriggerAiResponse(string $message): bool
    {
        if (! $this->hasAiAgent()) {
            return false;
        }

        if (empty($this->trigger_words)) {
            return true;
        }

        $triggerWords = is_array($this->trigger_words)
            ? $this->trigger_words
            : array_map('trim', explode(',', strtolower($this->trigger_words)));
        $messageWords = str_word_count(strtolower($message), 1);

        foreach ($triggerWords as $trigger) {
            if (in_array(trim(strtolower($trigger)), $messageWords, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Update WhatsApp account status from webhook.
     */
    public static function updateStatusFromWebhook(string $sessionId, string $newStatus, ?string $phoneNumber = null): bool
    {
        /** @var WhatsAppAccount|null $account */
        $account = self::where('session_id', $sessionId)->first();

        if (! $account) {
            // Log or handle the case where the session is not found
            Log::warning('WhatsApp account not found for webhook update', [
                'session_id' => $sessionId,
                'new_status' => $newStatus,
                'phone_number' => $phoneNumber,
            ]);

            return false;
        }

        try {
            $updateData = [
                'status' => WhatsAppStatus::from($newStatus),
            ];

            if ($phoneNumber) {
                $updateData['phone_number'] = $phoneNumber;
            }

            $account->update($updateData);

            Log::info('WhatsApp account status updated from webhook', [
                'session_id' => $sessionId,
                'old_status' => $account->status->value,
                'new_status' => $newStatus,
                'phone_number' => $phoneNumber,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to update WhatsApp account status from webhook', [
                'session_id' => $sessionId,
                'new_status' => $newStatus,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
