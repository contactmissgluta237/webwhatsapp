<?php

namespace App\Models;

use App\Enums\ExternalTransactionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * == Properties ==
 *
 * @property int $id
 * @property int $system_account_id
 * @property ExternalTransactionType $type
 * @property float $amount
 * @property string|null $sender_name
 * @property string|null $sender_account
 * @property string|null $receiver_name
 * @property string|null $receiver_account
 * @property string|null $description
 * @property int|null $created_by
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * == Relationships ==
 * @property-read SystemAccount $systemAccount
 * @property-read User|null $creator
 */
final class SystemAccountTransaction extends Model
{
    use HasFactory;

    // ================================================================================
    // PROPERTIES
    // ================================================================================

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'system_account_id',
        'type',
        'amount',
        'sender_name',
        'sender_account',
        'receiver_name',
        'receiver_account',
        'description',
        'created_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string|class-string>
     */
    protected $casts = [
        'type' => ExternalTransactionType::class,
        'amount' => 'decimal:2',
    ];

    // ================================================================================
    // RELATIONSHIPS
    // ================================================================================

    public function systemAccount(): BelongsTo
    {
        return $this->belongsTo(SystemAccount::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
