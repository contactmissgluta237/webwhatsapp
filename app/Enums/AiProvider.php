<?php

declare(strict_types=1);

namespace App\Enums;

use App\Services\AI\AiServiceInterface;
use App\Services\AI\AnthropicService;
use App\Services\AI\DeepSeekService;
use App\Services\AI\OllamaService;
use App\Services\AI\OpenAiService;
use Spatie\Enum\Enum;

/**
 * @method static self OLLAMA()
 * @method static self OPENAI()
 * @method static self ANTHROPIC()
 * @method static self DEEPSEEK()
 */
final class AiProvider extends Enum
{
    protected static function values(): array
    {
        return [
            'OLLAMA' => 'ollama',
            'OPENAI' => 'openai',
            'ANTHROPIC' => 'anthropic',
            'DEEPSEEK' => 'deepseek',
        ];
    }

    protected static function labels(): array
    {
        return [
            'OLLAMA' => 'Ollama',
            'OPENAI' => 'OpenAI',
            'ANTHROPIC' => 'Anthropic (Claude)',
            'DEEPSEEK' => 'DeepSeek',
        ];
    }

    // ================================================================================
    // SERVICE FACTORY
    // ================================================================================

    public function getServiceClass(): string
    {
        return match ($this->value) {
            'ollama' => OllamaService::class,
            'openai' => OpenAiService::class,
            'anthropic' => AnthropicService::class,
            'deepseek' => DeepSeekService::class,
            default => throw new \InvalidArgumentException("Service non supporté: {$this->value}")
        };
    }

    public function createService(): AiServiceInterface
    {
        return app($this->getServiceClass());
    }

    // ================================================================================
    // CONFIGURATION
    // ================================================================================

    public function getDefaultConfig(): array
    {
        return match ($this->value) {
            'ollama' => [
                'temperature' => 0.7,
                'max_tokens' => 1000,
                'top_p' => 0.9,
                'top_k' => 40,
                'stream' => false,
            ],
            'openai' => [
                'temperature' => 0.7,
                'max_tokens' => 1500,
                'frequency_penalty' => 0.0,
                'presence_penalty' => 0.0,
            ],
            'anthropic' => [
                'max_tokens' => 1500,
                'temperature' => 0.7,
                'top_p' => 0.9,
            ],
            'deepseek' => [
                'temperature' => 0.7,
                'max_tokens' => 1500,
                'top_p' => 0.95,
            ],
            default => []
        };
    }

    public function getRequiredFields(): array
    {
        return match ($this->value) {
            'ollama' => ['endpoint_url', 'model_identifier'],
            'openai' => ['api_key', 'model_identifier'],
            'anthropic' => ['api_key', 'model_identifier'],
            'deepseek' => ['api_key', 'model_identifier', 'endpoint_url'],
            default => []
        };
    }

    public function requiresApiKey(): bool
    {
        return match ($this->value) {
            'ollama' => false,
            'openai', 'anthropic', 'deepseek' => true,
            default => true
        };
    }

    public function getDefaultEndpoint(): ?string
    {
        return match ($this->value) {
            'openai' => 'https://api.openai.com/v1',
            'anthropic' => 'https://api.anthropic.com',
            'deepseek' => 'https://api.deepseek.com',
            'ollama' => null, // Defined by user
            default => null
        };
    }

    // ================================================================================
    // UI HELPERS
    // ================================================================================

    public function getBadgeClass(): string
    {
        return match ($this->value) {
            'ollama' => 'bg-blue-100 text-blue-800',
            'openai' => 'bg-green-100 text-green-800',
            'anthropic' => 'bg-purple-100 text-purple-800',
            'deepseek' => 'bg-orange-100 text-orange-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    public function getIcon(): string
    {
        return match ($this->value) {
            'ollama' => 'server',
            'openai' => 'sparkles',
            'anthropic' => 'brain',
            'deepseek' => 'academic-cap',
            default => 'cpu-chip',
        };
    }

    // ================================================================================
    // FACTORY METHODS
    // ================================================================================

    /**
     * Créer un modèle avec les valeurs par défaut du provider
     */
    public function createModelDefaults(array $overrides = []): array
    {
        return array_merge([
            'provider' => $this->value,
            'requires_api_key' => $this->requiresApiKey(),
            'endpoint_url' => $this->getDefaultEndpoint(),
            'model_config' => $this->getDefaultConfig(),
            'is_active' => false,
            'is_default' => false,
        ], $overrides);
    }
}
