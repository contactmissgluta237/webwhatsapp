<?php

namespace App\Models\Geography;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Carbon;

/**
 * == Properties ==
 *
 * @property int $id
 * @property int $country_id
 * @property string $name
 * @property bool $is_active
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * == Relationships ==
 * @property-read Country $country
 * @property-read Collection|Municipality[] $municipalities
 * @property-read Collection|Neighborhood[] $neighborhoods
 */
class City extends Model
{
    use HasFactory;

    protected $fillable = [
        'country_id',
        'name',
        'is_active',
    ];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function municipalities(): HasMany
    {
        return $this->hasMany(Municipality::class);
    }

    public function neighborhoods(): HasManyThrough
    {
        return $this->hasManyThrough(Neighborhood::class, Municipality::class);
    }
}
