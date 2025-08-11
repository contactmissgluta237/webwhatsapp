<?php

namespace App\Models;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
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
 * @property int $user_id
 * @property float $balance
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * == Relationships ==
 * @property-read User $user
 * @property-read Collection|InternalTransaction[] $internalTransactions
 * @property-read Collection|InternalTransaction[] $internalCredits
 * @property-read Collection|InternalTransaction[] $internalDebits
 * @property-read Collection|InternalTransaction[] $completedInternalTransactions
 * @property-read Collection|ExternalTransaction[] $externalTransactions
 */
class Wallet extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'balance',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
    ];

    protected $attributes = [
        'balance' => 0.00,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relations avec les transactions internes
     */
    public function internalTransactions(): HasMany
    {
        return $this->hasMany(InternalTransaction::class);
    }

    /**
     * Transactions internes de type crédit
     */
    public function internalCredits(): HasMany
    {
        return $this->hasMany(InternalTransaction::class)
            ->where('transaction_type', TransactionType::CREDIT());
    }

    /**
     * Transactions internes de type débit
     */
    public function internalDebits(): HasMany
    {
        return $this->hasMany(InternalTransaction::class)
            ->where('transaction_type', TransactionType::DEBIT());
    }

    /**
     * Transactions internes terminées
     */
    public function completedInternalTransactions(): HasMany
    {
        return $this->hasMany(InternalTransaction::class)
            ->where('status', TransactionStatus::COMPLETED());
    }

    /**
     * Relations avec les transactions externes (via l'utilisateur du wallet)
     */
    public function externalTransactions(): HasMany
    {
        return $this->hasMany(ExternalTransaction::class, 'user_id', 'user_id');
    }
}
