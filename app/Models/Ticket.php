<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * == Properties ==
 *
 * @property int $id
 * @property string $ticket_number
 * @property int $user_id
 * @property int|null $assigned_to
 * @property string $title
 * @property string $description
 * @property TicketStatus $status
 * @property TicketPriority $priority
 * @property Carbon|null $closed_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * == Relationships ==
 * @property-read User $user
 * @property-read User|null $assignedTo
 * @property-read Collection|TicketMessage[] $messages
 */
final class Ticket extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'ticket_number',
        'user_id',
        'assigned_to',
        'title',
        'description',
        'status',
        'priority',
        'closed_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string|class-string>
     */
    protected $casts = [
        'closed_at' => 'datetime',
        'status' => TicketStatus::class,
        'priority' => TicketPriority::class,
    ];

    // ================================================================================
    // BOOT METHOD
    // ================================================================================

    protected static function boot(): void
    {
        parent::boot();

        self::creating(function (self $ticket) {
            $ticket->ticket_number = self::generateUniqueTicketNumber();
        });
    }

    // ================================================================================
    // RELATIONSHIPS
    // ================================================================================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(TicketMessage::class);
    }

    // ================================================================================
    // PUBLIC METHODS
    // ================================================================================

    public function isOpen(): bool
    {
        return $this->status->equals(TicketStatus::OPEN());
    }

    public function isClosed(): bool
    {
        return $this->status->equals(TicketStatus::CLOSED());
    }

    public function isReplied(): bool
    {
        return $this->status->equals(TicketStatus::REPLIED());
    }

    public function isResolved(): bool
    {
        return $this->status->equals(TicketStatus::RESOLVED());
    }

    // ================================================================================
    // PROTECTED METHODS
    // ================================================================================

    protected static function generateUniqueTicketNumber(): string
    {
        $date = now()->format('dmY');
        $todayCount = self::whereDate('created_at', now()->toDateString())->count();
        $sequentialNumber = str_pad((string) ($todayCount + 1), 3, '0', STR_PAD_LEFT);

        return "TICKET-{$date}-{$sequentialNumber}";
    }
}
