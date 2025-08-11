<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * == Properties ==
 *
 * @property int $id
 * @property PaymentMethod $type
 * @property float $balance
 * @property bool $is_active
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * == Relationships ==
 * @property-read Collection|SystemAccountTransaction[] $transactions
 */
final class SystemAccount extends Model
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
        'type',
        'balance',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string|class-string>
     */
    protected $casts = [
        'type' => PaymentMethod::class,
        'is_active' => 'boolean',
        'balance' => 'decimal:2',
    ];

    // ================================================================================
    // RELATIONSHIPS
    // ================================================================================

    public function transactions(): HasMany
    {
        return $this->hasMany(SystemAccountTransaction::class, 'system_account_id');
    }
}
