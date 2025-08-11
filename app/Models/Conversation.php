<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * == Properties ==
 *
 * @property int $id
 * @property int $whatsapp_account_id
 * @property string $chat_id
 * @property string $contact_phone
 * @property string|null $contact_name
 * @property bool $is_group
 * @property Carbon|null $last_message_at
 * @property int $unread_count
 * @property bool $is_ai_enabled
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * == Relationships ==
 * @property-read WhatsAppAccount $whatsappAccount
 * @property-read Collection<int, Message> $messages
 * @property-read Message|null $lastMessage
 */
final class Conversation extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'whatsapp_account_id',
        'chat_id',
        'contact_phone',
        'contact_name',
        'is_group',
        'last_message_at',
        'unread_count',
        'is_ai_enabled',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string|class-string>
     */
    protected $casts = [
        'is_group' => 'boolean',
        'is_ai_enabled' => 'boolean',
        'last_message_at' => 'datetime',
        'unread_count' => 'integer',
    ];

    // ================================================================================
    // RELATIONSHIPS
    // ================================================================================

    public function whatsappAccount(): BelongsTo
    {
        return $this->belongsTo(WhatsAppAccount::class);
    }

    /**
     * @return HasMany<Message, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    /**
     * @return HasMany<Message, $this>
     */
    public function lastMessage(): HasMany
    {
        return $this->hasMany(Message::class)->latest();
    }

    // ================================================================================
    // PUBLIC METHODS
    // ================================================================================

    public function getDisplayName(): string
    {
        return $this->contact_name ?? $this->contact_phone;
    }

    public function hasUnreadMessages(): bool
    {
        return $this->unread_count > 0;
    }

    public function markAsRead(): void
    {
        $this->update(['unread_count' => 0]);
    }

    public function incrementUnreadCount(): void
    {
        $this->increment('unread_count');
    }

    public function updateLastMessage(Carbon $timestamp): void
    {
        $this->update(['last_message_at' => $timestamp]);
    }

    /**
     * @return array{inbound: int, outbound: int, total: int}
     */
    public function getTodayMessagesCount(): array
    {
        /** @var Collection<int, Message> $messages */
        $messages = $this->messages()
            ->whereDate('created_at', today())
            ->get();

        $inbound = $messages->where('direction', 'inbound')->count();
        $outbound = $messages->where('direction', 'outbound')->count();

        return [
            'inbound' => $inbound,
            'outbound' => $outbound,
            'total' => $inbound + $outbound,
        ];
    }

    public function getAverageResponseTime(): ?float
    {
        /** @var Collection<int, Message> $allMessages */
        $allMessages = $this->messages()
            ->orderBy('created_at')
            ->get();

        $conversations = $allMessages->groupBy(function (Message $message): string {
            return $message->created_at->format('Y-m-d H:i');
        });

        $responseTimes = [];

        foreach ($conversations as $group) {
            /** @var Message|null $inbound */
            $inbound = $group->where('direction', 'inbound')->first();
            /** @var Message|null $outbound */
            $outbound = $group->where('direction', 'outbound')->first();

            if ($inbound && $outbound && $outbound->created_at > $inbound->created_at) {
                $responseTimes[] = $outbound->created_at->diffInSeconds($inbound->created_at);
            }
        }

        return empty($responseTimes) ? null : array_sum($responseTimes) / count($responseTimes);
    }
}
