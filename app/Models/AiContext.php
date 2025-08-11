<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ResponseTone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * == Properties ==
 *
 * @property int $id
 * @property int $whatsapp_account_id
 * @property string $business_context
 * @property ResponseTone $response_tone
 * @property string|null $greeting_message
 * @property string|null $fallback_message
 * @property bool $auto_reply_enabled
 * @property int $response_delay_seconds
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * == Relationships ==
 * @property-read WhatsAppAccount $whatsappAccount
 */
final class AiContext extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'whatsapp_account_id',
        'business_context',
        'response_tone',
        'greeting_message',
        'fallback_message',
        'auto_reply_enabled',
        'response_delay_seconds',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string|class-string>
     */
    protected $casts = [
        'response_tone' => ResponseTone::class,
        'auto_reply_enabled' => 'boolean',
        'response_delay_seconds' => 'integer',
    ];

    // ================================================================================
    // RELATIONSHIPS
    // ================================================================================

    public function whatsappAccount(): BelongsTo
    {
        return $this->belongsTo(WhatsAppAccount::class);
    }

    // ================================================================================
    // PUBLIC METHODS
    // ================================================================================

    public function isAutoReplyEnabled(): bool
    {
        return $this->auto_reply_enabled;
    }

    public function getFullPrompt(): string
    {
        $prompt = "Contexte Business: {$this->business_context}\n";
        $prompt .= "Ton de réponse: {$this->response_tone->label}\n";

        if ($this->greeting_message) {
            $prompt .= "Message d'accueil: {$this->greeting_message}\n";
        }

        if ($this->fallback_message) {
            $prompt .= "Message de secours: {$this->fallback_message}\n";
        }

        return $prompt;
    }

    public function shouldDelay(): bool
    {
        return $this->response_delay_seconds > 0;
    }

    public function getDelayInSeconds(): int
    {
        return max(1, $this->response_delay_seconds);
    }
}
