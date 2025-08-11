<?php

namespace App\Models;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * == Properties ==
 *
 * @property int $id
 * @property int $wallet_id
 * @property float $amount
 * @property TransactionType $transaction_type
 * @property TransactionStatus $status
 * @property string|null $description
 * @property string|null $related_type
 * @property int|null $related_id
 * @property int|null $recipient_user_id
 * @property int $created_by
 * @property Carbon|null $completed_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * == Relationships ==
 * @property-read Wallet $wallet
 * @property-read User $creator
 * @property-read User|null $recipient
 * @property-read Model|null $related
 */
class InternalTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'wallet_id',
        'amount',
        'transaction_type',
        'status',
        'description',
        'related_type',
        'related_id',
        'recipient_user_id',
        'created_by',
        'completed_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'transaction_type' => TransactionType::class,
        'status' => TransactionStatus::class,
        'completed_at' => 'datetime',
    ];

    /**
     * Wallet relationship
     */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    /**
     * Creator user relationship
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Recipient user relationship (for transfers)
     */
    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_user_id');
    }

    /**
     * Polymorphic relationship with the related entity
     */
    public function related()
    {
        return $this->morphTo('related');
    }

    /**
     * Scope pour les crédits
     */
    public function scopeCredits($query)
    {
        return $query->where('transaction_type', TransactionType::CREDIT());
    }

    /**
     * Scope pour les débits
     */
    public function scopeDebits($query)
    {
        return $query->where('transaction_type', TransactionType::DEBIT());
    }

    /**
     * Scope pour les transactions terminées
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', TransactionStatus::COMPLETED());
    }

    /**
     * Vérifier si la transaction est terminée
     */
    public function isCompleted(): bool
    {
        return $this->status === TransactionStatus::COMPLETED();
    }

    /**
     * Vérifier si c'est un crédit
     */
    public function isCredit(): bool
    {
        return $this->transaction_type === TransactionType::CREDIT();
    }

    /**
     * Vérifier si c'est un débit
     */
    public function isDebit(): bool
    {
        return $this->transaction_type === TransactionType::DEBIT();
    }
}
