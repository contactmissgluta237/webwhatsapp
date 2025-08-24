<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * == Properties ==
 *
 * @property int $id
 * @property string $name
 * @property string $display_name
 * @property string|null $description
 * @property float $price
 * @property string $currency
 * @property int $messages_limit
 * @property int $context_limit
 * @property int $accounts_limit
 * @property int $products_limit
 * @property int|null $duration_days
 * @property bool $is_recurring
 * @property bool $one_time_only
 * @property array|null $features
 * @property bool $is_active
 * @property int $sort_order
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * == Relationships ==
 * @property-read \Illuminate\Database\Eloquent\Collection|UserSubscription[] $subscriptions
 */
class Package extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'display_name',
        'description',
        'price',
        'currency',
        'messages_limit',
        'context_limit',
        'accounts_limit',
        'products_limit',
        'duration_days',
        'is_recurring',
        'one_time_only',
        'features',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_recurring' => 'boolean',
        'one_time_only' => 'boolean',
        'is_active' => 'boolean',
        'features' => 'array',
    ];

    // ================================================================================
    // RELATIONSHIPS
    // ================================================================================

    public function subscriptions(): HasMany
    {
        return $this->hasMany(UserSubscription::class);
    }

    // ================================================================================
    // QUERY SCOPES
    // ================================================================================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('price');
    }

    // ================================================================================
    // TYPE CHECKING METHODS
    // ================================================================================

    public function isTrial(): bool
    {
        return $this->name === 'trial';
    }

    public function isStarter(): bool
    {
        return $this->name === 'starter';
    }

    public function isBusiness(): bool
    {
        return $this->name === 'business';
    }

    public function isPro(): bool
    {
        return $this->name === 'pro';
    }

    // ================================================================================
    // FEATURE CHECKING METHODS
    // ================================================================================

    public function allowsProducts(): bool
    {
        return $this->products_limit > 0;
    }

    public function allowsMultipleAccounts(): bool
    {
        return $this->accounts_limit > 1;
    }

    public function hasWeeklyReports(): bool
    {
        return in_array('weekly_reports', $this->features ?? []);
    }

    public function hasPrioritySupport(): bool
    {
        return in_array('priority_support', $this->features ?? []);
    }

    public function hasApiAccess(): bool
    {
        return in_array('api_access', $this->features ?? []);
    }

    // ================================================================================
    // UTILITY METHODS
    // ================================================================================

    public function isLimitedTime(): bool
    {
        return $this->duration_days !== null;
    }

    public function getDurationInDays(): ?int
    {
        return $this->duration_days;
    }

    public function getFormattedPrice(): string
    {
        if ($this->price == 0) {
            return 'Gratuit';
        }

        return number_format($this->price, 0, ',', ' ').' '.$this->currency;
    }

    public function getDisplayFeatures(): array
    {
        $displayFeatures = [];

        if ($this->allowsProducts()) {
            $displayFeatures[] = $this->products_limit.' produit'.($this->products_limit > 1 ? 's' : '');
        }

        if ($this->allowsMultipleAccounts()) {
            $displayFeatures[] = $this->accounts_limit.' compte'.($this->accounts_limit > 1 ? 's' : '').' WhatsApp';
        }

        if ($this->hasWeeklyReports()) {
            $displayFeatures[] = 'Rapports hebdomadaires';
        }

        if ($this->hasPrioritySupport()) {
            $displayFeatures[] = 'Support prioritaire';
        }

        if ($this->hasApiAccess()) {
            $displayFeatures[] = 'Accès API';
        }

        return $displayFeatures;
    }

    // ================================================================================
    // STATIC METHODS
    // ================================================================================

    public static function findByName(string $name): ?self
    {
        return static::where('name', $name)->first();
    }

    public static function getTrialPackage(): ?self
    {
        return static::findByName('trial');
    }

    public static function getDefaultPackage(): ?self
    {
        return static::findByName('starter');
    }

    public static function getActivePackages()
    {
        return static::active()->ordered()->get();
    }
}
