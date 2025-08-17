<?php

declare(strict_types=1);

namespace App\Models;

use App\Casts\SpatieEnumCast;
use App\Enums\WhatsAppStatus;
use App\Traits\HasMediaCollections;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
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
 * == Relationships ==
 * @property-read User $user
 * @property-read Collection|WhatsAppConversation[] $conversations
 * @property-read AiContext|null $aiContext
 * @property-read AiModel|null $aiModel
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

    public function linkedProducts(): BelongsToMany
    {
        return $this->belongsToMany(UserProduct::class, 'whatsapp_account_products');
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
        $conversations = $this->conversations()->with(['messages' => function ($query) {
            $query->whereDate('created_at', today());
        }])->get();

        $inbound = 0;
        $outbound = 0;

        foreach ($conversations as $conversation) {
            /** @var \App\Models\WhatsAppMessage $message */
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

    public function getEffectiveAiModelId(): ?int
    {
        if ($this->ai_model_id) {
            return $this->ai_model_id;
        }

        $defaultModel = AiModel::where('is_default', true)->where('is_active', true)->first()
            ?? AiModel::where('is_active', true)->first();

        return $defaultModel?->id;
    }

    public function getEffectiveAiModel(): ?AiModel
    {
        $modelId = $this->getEffectiveAiModelId();

        return $modelId ? AiModel::find($modelId) : null;
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

    /**
     * Retourne le prompt par défaut pour l'agent IA
     */
    public static function getDefaultAgentPrompt(): string
    {
        return "Je suis un commercial, je travaille dans l'équipe commerciale et support. J'aide nos clients avec leurs projets tech et leurs questions. 

🔥 RÈGLE ABSOLUE : Je ne dois JAMAIS inventer d'informations que je ne connais pas avec certitude. Si je ne connais pas quelque chose (coordonnées, prix, détails techniques, dates, etc.), je dis honnêtement que je reviens vers toi avec la bonne info.

Je réponds de manière naturelle et professionnelle, comme un vrai membre de l'équipe.";
    }

    public function updateAiConfiguration(array $config): void
    {
        $allowedFields = ['ai_model_id', 'agent_prompt', 'trigger_words', 'response_time'];
        $filteredConfig = array_intersect_key($config, array_flip($allowedFields));

        $this->update($filteredConfig);
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
}
