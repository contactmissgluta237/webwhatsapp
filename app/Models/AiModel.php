<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * == Properties ==
 *
 * @property int $id
 * @property string $name
 * @property string $provider
 * @property string $model_identifier
 * @property string|null $description
 * @property string|null $endpoint_url
 * @property bool $requires_api_key
 * @property string|null $api_key
 * @property array|null $model_config
 * @property bool $is_active
 * @property bool $is_default
 * @property float|null $cost_per_1k_tokens
 * @property int|null $max_context_length
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * == Relationships ==
 * @property-read Collection|WhatsAppAccount[] $whatsappAccounts
 */
final class AiModel extends Model
{
    use HasFactory;

    protected $table = 'ai_models';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'provider',
        'model_identifier',
        'description',
        'endpoint_url',
        'requires_api_key',
        'api_key',
        'model_config',
        'is_active',
        'is_default',
        'cost_per_1k_tokens',
        'max_context_length',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string|class-string>
     */
    protected $casts = [
        'requires_api_key' => 'boolean',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'model_config' => 'array',
        'cost_per_1k_tokens' => 'decimal:6',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'api_key',
    ];

    // ================================================================================
    // RELATIONSHIPS
    // ================================================================================

    public function whatsappAccounts(): HasMany
    {
        return $this->hasMany(WhatsAppAccount::class);
    }

    // ================================================================================
    // SCOPES
    // ================================================================================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByProvider($query, string $provider)
    {
        return $query->where('provider', $provider);
    }

    // ================================================================================
    // PUBLIC METHODS
    // ================================================================================

    public function isOpenAI(): bool
    {
        return $this->provider === 'openai';
    }

    public function isAnthropic(): bool
    {
        return $this->provider === 'anthropic';
    }

    public function isOllama(): bool
    {
        return $this->provider === 'ollama';
    }

    public function isDeepSeek(): bool
    {
        return $this->provider === 'deepseek';
    }

    public function hasApiKey(): bool
    {
        return !empty($this->api_key);
    }

    public function isConfigured(): bool
    {
        if ($this->requires_api_key && !$this->hasApiKey()) {
            return false;
        }

        return !empty($this->endpoint_url);
    }

    public function getEstimatedCostFor(int $tokens): float
    {
        if (!$this->cost_per_1k_tokens) {
            return 0.0;
        }

        return ($tokens / 1000) * $this->cost_per_1k_tokens;
    }

    public function getProviderBadgeColor(): string
    {
        return match ($this->provider) {
            'openai' => 'success',
            'anthropic' => 'warning',
            'deepseek' => 'info',
            'ollama' => 'primary',
            default => 'secondary',
        };
    }

    public static function getDefault(): ?self
    {
        return static::where('is_default', true)
            ->where('is_active', true)
            ->first();
    }

    public static function getActiveModels(): Collection
    {
        return static::where('is_active', true)
            ->orderBy('is_default', 'desc')
            ->orderBy('name')
            ->get();
    }
}
