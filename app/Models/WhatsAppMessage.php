<?php

declare(strict_types=1);

namespace App\Models;

use App\Casts\SpatieEnumCast;
use App\Enums\MessageDirection;
use App\Enums\MessageSubtype;
use App\Enums\MessageType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * == Properties ==
 *
 * @property int $id
 * @property int $whatsapp_conversation_id
 * @property string|null $whatsapp_message_id
 * @property MessageDirection $direction
 * @property string $content
 * @property MessageType $message_type
 * @property bool $is_ai_generated
 * @property string|null $ai_model_used
 * @property float|null $ai_confidence
 * @property Carbon|null $processed_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * == Relationships ==
 * @property-read WhatsAppConversation $conversation
 */
final class WhatsAppMessage extends Model
{
    use HasFactory;

    protected $table = 'whatsapp_messages';
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'whatsapp_conversation_id',
        'whatsapp_message_id',
        'direction',
        'content',
        'message_type',
        'message_subtype',
        'media_urls',
        'is_ai_generated',
        'ai_model_used',
        'ai_confidence',
        'processed_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string|class-string>
     */
    protected $casts = [
        'direction' => SpatieEnumCast::class.':'.MessageDirection::class,
        'message_type' => SpatieEnumCast::class.':'.MessageType::class,
        'message_subtype' => SpatieEnumCast::class.':'.MessageSubtype::class,
        'media_urls' => 'json',
        'is_ai_generated' => 'boolean',
        'ai_confidence' => 'decimal:2',
        'processed_at' => 'datetime',
    ];

    // ================================================================================
    // RELATIONSHIPS
    // ================================================================================

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(WhatsAppConversation::class, 'whatsapp_conversation_id');
    }

    public function aiUsageLogs(): HasMany
    {
        return $this->hasMany(AiUsageLog::class, 'whatsapp_message_id');
    }

    public function messageUsageLog(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(\App\Models\MessageUsageLog::class, 'whatsapp_message_id');
    }

    // ================================================================================
    // PUBLIC METHODS
    // ================================================================================

    public function isInbound(): bool
    {
        return $this->direction->equals(MessageDirection::INBOUND());
    }

    public function isOutbound(): bool
    {
        return $this->direction->equals(MessageDirection::OUTBOUND());
    }

    public function isFromAi(): bool
    {
        return $this->is_ai_generated;
    }

    public function hasHighConfidence(): bool
    {
        return $this->ai_confidence && $this->ai_confidence >= 0.8;
    }

    public function getFormattedTime(): string
    {
        return $this->created_at->format('H:i');
    }

    public function getFormattedDate(): string
    {
        return $this->created_at->format('d/m/Y H:i');
    }

    public function isToday(): bool
    {
        return $this->created_at->isToday();
    }
}
