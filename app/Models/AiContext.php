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
        'response_delay_seconds',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string|class-string>
     */
    protected $casts = [
        'response_tone' => ResponseTone::class,
        'response_delay_seconds' => 'integer',
    ];

    // ================================================================================
    // RELATIONSHIPS
    // ================================================================================

    public function whatsappAccount(): BelongsTo
    {
        return $this->belongsTo(WhatsAppAccount::class);
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
