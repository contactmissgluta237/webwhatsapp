<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AiProvider;
use App\Models\AiModel;
use App\Services\AI\AiConfigurationService;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AiModel>
 */
final class AiModelFactory extends Factory
{
    protected $model = AiModel::class;

    public function definition(): array
    {
        $ollamaConfig = AiConfigurationService::getProviderConfig('ollama');
        
        return [
            'name' => $this->faker->words(2, true).' AI Model',
            'provider' => AiProvider::OLLAMA()->value,
            'model_identifier' => $ollamaConfig['model_identifier'],
            'description' => $this->faker->sentence(),
            'endpoint_url' => $ollamaConfig['endpoint_url'],
            'requires_api_key' => $ollamaConfig['requires_api_key'],
            'api_key' => $ollamaConfig['api_key'],
            'model_config' => json_encode($ollamaConfig['default_config']),
            'is_active' => true,
            'is_default' => false,
            'cost_per_1k_tokens' => $ollamaConfig['cost_per_1k_tokens'],
            'max_context_length' => $ollamaConfig['max_context_length'],
        ];
    }

    public function ollama(): static
    {
        return $this->state(function (array $attributes) {
            $ollamaConfig = AiConfigurationService::getProviderConfig('ollama');
            
            return [
                'provider' => AiProvider::OLLAMA()->value,
                'model_identifier' => $this->faker->randomElement(['gemma2:2b', 'llama3.1:8b', 'mistral:latest']),
                'endpoint_url' => $ollamaConfig['endpoint_url'],
                'requires_api_key' => $ollamaConfig['requires_api_key'],
                'api_key' => $ollamaConfig['api_key'],
                'cost_per_1k_tokens' => $ollamaConfig['cost_per_1k_tokens'],
            ];
        });
    }

    public function openai(): static
    {
        return $this->state(function (array $attributes) {
            $openaiConfig = AiConfigurationService::getProviderConfig('openai');
            
            return [
                'provider' => AiProvider::OPENAI()->value,
                'model_identifier' => $this->faker->randomElement(['gpt-4o-mini', 'gpt-3.5-turbo', 'gpt-4']),
                'endpoint_url' => $openaiConfig['endpoint_url'],
                'requires_api_key' => $openaiConfig['requires_api_key'],
                'api_key' => 'sk-test-'.$this->faker->lexify('???????????????????????'),
                'cost_per_1k_tokens' => $openaiConfig['cost_per_1k_tokens'],
            ];
        });
    }

    public function anthropic(): static
    {
        return $this->state(function (array $attributes) {
            $anthropicConfig = AiConfigurationService::getProviderConfig('anthropic');
            
            return [
                'provider' => AiProvider::ANTHROPIC()->value,
                'model_identifier' => $anthropicConfig['model_identifier'],
                'endpoint_url' => $anthropicConfig['endpoint_url'],
                'requires_api_key' => $anthropicConfig['requires_api_key'],
                'api_key' => 'sk-ant-test-'.$this->faker->lexify('???????????????????????'),
                'cost_per_1k_tokens' => $anthropicConfig['cost_per_1k_tokens'],
            ];
        });
    }

    public function deepseek(): static
    {
        return $this->state(function (array $attributes) {
            $deepseekConfig = AiConfigurationService::getProviderConfig('deepseek');
            
            return [
                'provider' => AiProvider::DEEPSEEK()->value,
                'model_identifier' => $deepseekConfig['model_identifier'],
                'endpoint_url' => $deepseekConfig['endpoint_url'],
                'requires_api_key' => $deepseekConfig['requires_api_key'],
                'api_key' => 'sk-ds-test-'.$this->faker->lexify('???????????????????????'),
                'cost_per_1k_tokens' => $deepseekConfig['cost_per_1k_tokens'],
            ];
        });
    }

    public function active(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'is_active' => true,
            ];
        });
    }

    public function inactive(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'is_active' => false,
            ];
        });
    }

    public function default(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'is_default' => true,
                'is_active' => true,
            ];
        });
    }
}
