<?php

declare(strict_types=1);

namespace App\Models;

use App\Casts\SpatieEnumCast;
use App\Enums\WhatsAppStatus;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

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
 * @property bool $ai_agent_enabled
 * @property int|null $ai_model_id
 * @property string|null $ai_prompt
 * @property string|null $ai_trigger_words
 * @property string|null $ai_response_time
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * == Relationships ==
 * @property-read User $user
 * @property-read Collection|Conversation[] $conversations
 * @property-read AiContext|null $aiContext
 * @property-read AiModel|null $aiModel
 */
final class WhatsAppAccount extends Model
{
    use HasFactory;

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
        'agent_prompt',
        'trigger_words',
        'response_time',
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
        'trigger_words' => 'array',
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
        return $this->hasMany(Conversation::class, 'whatsapp_account_id');
    }

    public function aiContext(): HasOne
    {
        return $this->hasOne(AiContext::class);
    }

    public function aiModel(): BelongsTo
    {
        return $this->belongsTo(AiModel::class);
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
        /** @var \Illuminate\Database\Eloquent\Collection<\App\Models\Conversation> $conversations */
        $conversations = $this->conversations()->with(['messages' => function ($query) {
            $query->whereDate('created_at', today());
        }])->get();

        $inbound = 0;
        $outbound = 0;

        foreach ($conversations as $conversation) {
            /** @var \App\Models\Message $message */
            foreach ($conversation->messages as $message) {
                if ($message->direction === 'inbound') {
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

    public function getAiModel(): ?AiModel
    {
        return $this->aiModel;
    }

    public function enableAiAgent(int $aiModelId, ?string $prompt = null, ?string $triggerWords = null, ?string $responseTime = null): void
    {
        $this->update([
            'agent_enabled' => true,
            'ai_model_id' => $aiModelId,
            'agent_prompt' => $prompt,
            'trigger_words' => $triggerWords,
            'response_time' => $responseTime,
        ]);
    }

    public function disableAiAgent(): void
    {
        $this->update([
            'agent_enabled' => false,
        ]);
    }

    public function updateAiConfiguration(array $config): void
    {
        $allowedFields = ['ai_model_id', 'agent_prompt', 'trigger_words', 'response_time'];
        $filteredConfig = array_intersect_key($config, array_flip($allowedFields));
        
        $this->update($filteredConfig);
    }

    public function shouldTriggerAiResponse(string $message): bool
    {
        if (!$this->hasAiAgent()) {
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
}
