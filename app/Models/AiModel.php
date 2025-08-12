<?php

declare(strict_types=1);

namespace App\Models;

use App\Casts\SpatieEnumCast;
use App\Enums\AiProvider;
use App\Services\AI\AiServiceInterface;
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
 * @property AiProvider $provider
 * @property string $model_identifier
 * @property string|null $description
 * @property string|null $endpoint_url
 * @property bool $requires_api_key
 * @property string|null $api_key
 * @property array $model_config
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

    protected $casts = [
        'provider' => SpatieEnumCast::class.':'.AiProvider::class,
        'requires_api_key' => 'boolean',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'model_config' => 'array',
        'cost_per_1k_tokens' => 'decimal:6',
    ];

    protected $hidden = [
        'api_key',
    ];

    protected $attributes = [
        'model_config' => '{}',
        'requires_api_key' => true,
        'is_active' => false,
        'is_default' => false,
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

    public function scopeByProvider($query, AiProvider $provider)
    {
        return $query->where('provider', $provider);
    }

    // ================================================================================
    // CONFIGURATION METHODS
    // ================================================================================

    /**
     * Retourne la configuration mergée (défaut + modèle + override)
     */
    public function getMergedConfig(array $overrideConfig = []): array
    {
        return array_merge(
            $this->provider->getDefaultConfig(),
            $this->model_config,
            $overrideConfig
        );
    }

    /**
     * Retourne une valeur de configuration spécifique
     */
    public function getConfigValue(string $key, mixed $default = null, array $overrides = []): mixed
    {
        return $this->getMergedConfig($overrides)[$key] ?? $default;
    }

    // ================================================================================
    // VALIDATION METHODS
    // ================================================================================

    public function hasApiKey(): bool
    {
        return ! $this->requires_api_key || ! empty($this->api_key);
    }

    public function hasEndpoint(): bool
    {
        return ! empty($this->endpoint_url);
    }

    public function isConfigured(): bool
    {
        return $this->hasApiKey() && $this->hasEndpoint();
    }

    public function validateRequiredFields(): array
    {
        $errors = [];
        $requiredFields = $this->provider->getRequiredFields();

        foreach ($requiredFields as $field) {
            if (empty($this->$field)) {
                $errors[] = "Le champ '$field' est requis pour {$this->provider->getLabel()}";
            }
        }

        return $errors;
    }

    // ================================================================================
    // PROVIDER DELEGATION METHODS
    // ================================================================================

    public function getService(): AiServiceInterface
    {
        return $this->provider->createService();
    }

    public function testConnection(): bool
    {
        return $this->getService()->testConnection($this);
    }

    public function validateConfiguration(): bool
    {
        return $this->getService()->validateConfiguration($this);
    }

    public function getProviderBadgeColor(): string
    {
        return $this->provider->getBadgeColor();
    }

    public function getProviderIcon(): string
    {
        return $this->provider->getIcon();
    }

    // ================================================================================
    // COST CALCULATION
    // ================================================================================

    public function getEstimatedCostFor(int $tokens): float
    {
        if (! $this->cost_per_1k_tokens) {
            return 0.0;
        }

        return ($tokens / 1000) * (float) $this->cost_per_1k_tokens;
    }

    // ================================================================================
    // STATIC METHODS
    // ================================================================================

    public static function getDefault(): ?self
    {
        return self::where('is_default', true)
            ->where('is_active', true)
            ->first();
    }

    public static function getActiveModels(): Collection
    {
        return self::where('is_active', true)
            ->orderBy('is_default', 'desc')
            ->orderBy('name')
            ->get();
    }

    public static function getByProvider(AiProvider $provider): Collection
    {
        return self::where('provider', $provider)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }
}
