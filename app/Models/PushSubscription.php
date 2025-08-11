<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon; // Import for scope methods

/**
 * == Properties ==
 *
 * @property int $id
 * @property string $subscribable_type
 * @property int $subscribable_id
 * @property string $endpoint
 * @property string|null $public_key
 * @property string|null $auth_token
 * @property string|null $content_encoding
 * @property bool $is_active
 * @property string|null $user_agent
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * == Relationships ==
 * @property-read Model $subscribable
 *
 * == Accessors ==
 * @property-read string|null $p256dh_key
 * @property-read string|null $auth_token
 *
 * @mixin \Illuminate\Database\Eloquent\Builder
 *
 * == Scopes ==
 *
 * @method static Builder|PushSubscription active()
 * @method static Builder|PushSubscription forUser(int $userId)
 */
final class PushSubscription extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'subscribable_type',
        'subscribable_id',
        'endpoint',
        'public_key',
        'auth_token',
        'content_encoding',
        'is_active',
        'user_agent',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ================================================================================
    // RELATIONSHIPS
    // ================================================================================

    public function subscribable(): MorphTo
    {
        return $this->morphTo();
    }

    // ================================================================================
    // SCOPES
    // ================================================================================

    /**
     * Scope for active subscriptions only.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for a specific user (via polymorphic relation).
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('subscribable_type', User::class)
            ->where('subscribable_id', $userId);
    }

    // ================================================================================
    // METHODS
    // ================================================================================

    /**
     * Deactivate a subscription.
     */
    public function deactivate(): bool
    {
        return $this->update(['is_active' => false]);
    }

    /**
     * Activate a subscription.
     */
    public function activate(): bool
    {
        return $this->update(['is_active' => true]);
    }

    /**
     * Check if the subscription is valid.
     */
    public function isValid(): bool
    {
        return ! empty($this->endpoint) &&
               ! empty($this->public_key) &&
               ! empty($this->auth_token) &&
               ($this->is_active ?? true);
    }

    /**
     * Get a summary of the subscription (for logs).
     */
    public function getSummary(): string
    {
        return sprintf(
            'ID: %d, Type: %s, ID: %d, Endpoint: %s..., Active: %s',
            $this->id,
            class_basename($this->subscribable_type ?? 'Unknown'),
            $this->subscribable_id ?? 0,
            substr($this->endpoint ?? '', 0, 50),
            ($this->is_active ?? true) ? 'Yes' : 'No'
        );
    }

    /**
     * Get the P256dh key for WebPush (compatibility with existing column names).
     */
    public function getP256dhKeyAttribute(): ?string
    {
        return $this->public_key;
    }

    /**
     * Get the authentication token (compatibility with existing column names).
     */
    public function getAuthTokenAttribute(): ?string
    {
        return $this->attributes['auth_token'] ?? null;
    }
}
