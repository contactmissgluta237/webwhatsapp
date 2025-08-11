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
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * == Relationships ==
 * @property-read User $user
 * @property-read Collection|Conversation[] $conversations
 * @property-read AiContext|null $aiContext
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
}
