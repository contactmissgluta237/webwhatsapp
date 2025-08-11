<?php

namespace App\Models;

use App\Enums\ExternalTransactionType;
use App\Enums\PaymentMethod;
use App\Enums\TransactionMode;
use App\Enums\TransactionStatus;
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
 * @property ExternalTransactionType $transaction_type
 * @property TransactionMode $mode
 * @property TransactionStatus $status
 * @property string|null $external_transaction_id
 * @property string|null $description
 * @property PaymentMethod|null $payment_method
 * @property string|null $gateway_transaction_id
 * @property array|null $gateway_response
 * @property string|null $sender_name
 * @property string|null $sender_phone
 * @property string|null $sender_address
 * @property string|null $sender_bank
 * @property string|null $sender_account
 * @property string|null $receiver_name
 * @property string|null $receiver_phone
 * @property string|null $receiver_address
 * @property string|null $receiver_bank
 * @property string|null $receiver_account
 * @property int $created_by
 * @property int|null $approved_by
 * @property Carbon|null $completed_at
 * @property Carbon|null $approved_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * == Relationships ==
 * @property-read Wallet $wallet
 * @property-read User $creator
 * @property-read User|null $approver
 */
class ExternalTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'wallet_id',
        'amount',
        'transaction_type',
        'mode',
        'status',
        'external_transaction_id',
        'description',
        'payment_method',
        'gateway_transaction_id',
        'gateway_response',
        'sender_name',
        'sender_phone',
        'sender_address',
        'sender_bank',
        'sender_account',
        'receiver_name',
        'receiver_phone',
        'receiver_address',
        'receiver_bank',
        'receiver_account',
        'created_by',
        'approved_by',
        'completed_at',
        'approved_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'transaction_type' => ExternalTransactionType::class,
        'mode' => TransactionMode::class,
        'status' => TransactionStatus::class,
        'payment_method' => PaymentMethod::class,
        'gateway_response' => 'array',
        'completed_at' => 'datetime',
        'approved_at' => 'datetime',
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
     * Approver user relationship
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Scope for recharges
     */
    public function scopeRecharges($query)
    {
        return $query->where('transaction_type', ExternalTransactionType::RECHARGE());
    }

    /**
     * Scope for withdrawals
     */
    public function scopeWithdrawals($query)
    {
        return $query->where('transaction_type', ExternalTransactionType::WITHDRAWAL());
    }

    /**
     * Scope for automatic transactions
     */
    public function scopeAutomatic($query)
    {
        return $query->where('mode', TransactionMode::AUTOMATIC());
    }

    /**
     * Scope for manual transactions
     */
    public function scopeManual($query)
    {
        return $query->where('mode', TransactionMode::MANUAL());
    }

    /**
     * Scope for pending transactions
     */
    public function scopePending($query)
    {
        return $query->where('status', TransactionStatus::PENDING());
    }

    /**
     * Scope for completed transactions
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', TransactionStatus::COMPLETED());
    }

    /**
     * Scope for transactions that require approval
     */
    public function scopeNeedsApproval($query)
    {
        return $query->where('transaction_type', ExternalTransactionType::WITHDRAWAL())
            ->where('status', TransactionStatus::PENDING())
            ->whereNull('approved_by');
    }

    /**
     * Check if it's a recharge
     */
    public function isRecharge(): bool
    {
        return $this->transaction_type === ExternalTransactionType::RECHARGE();
    }

    /**
     * Check if it's a withdrawal
     */
    public function isWithdrawal(): bool
    {
        return $this->transaction_type === ExternalTransactionType::WITHDRAWAL();
    }

    /**
     * Check if the transaction is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === TransactionStatus::COMPLETED();
    }

    /**
     * Check if the transaction is pending
     */
    public function isPending(): bool
    {
        return $this->status === TransactionStatus::PENDING();
    }

    /**
     * Check if the transaction failed
     */
    public function isFailed(): bool
    {
        return $this->status === TransactionStatus::FAILED();
    }

    /**
     * Check if the transaction is automatic
     */
    public function isAutomatic(): bool
    {
        return $this->mode === TransactionMode::AUTOMATIC();
    }

    /**
     * Check if the transaction is manual
     */
    public function isManual(): bool
    {
        return $this->mode === TransactionMode::MANUAL();
    }

    /**
     * Vérifier si la transaction nécessite une approbation
     */
    public function needsApproval(): bool
    {
        return $this->isWithdrawal() && $this->isPending() && ! $this->approved_by;
    }

    /**
     * Vérifier si la transaction est approuvée
     */
    public function isApproved(): bool
    {
        return ! is_null($this->approved_by);
    }
}
