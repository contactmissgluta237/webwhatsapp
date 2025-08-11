<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TicketSenderType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * == Properties ==
 *
 * @property int $id
 * @property int $ticket_id
 * @property int $user_id
 * @property string $message
 * @property TicketSenderType $sender_type
 * @property bool $is_internal
 * @property Carbon|null $read_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * == Relationships ==
 * @property-read Ticket $ticket
 * @property-read User $user
 */
final class TicketMessage extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'ticket_id',
        'user_id',
        'message',
        'sender_type',
        'is_internal',
        'read_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string|class-string>
     */
    protected $casts = [
        'sender_type' => TicketSenderType::class,
        'is_internal' => 'boolean',
        'read_at' => 'datetime',
    ];

    // ================================================================================
    // RELATIONSHIPS
    // ================================================================================

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
