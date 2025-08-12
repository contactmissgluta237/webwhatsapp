<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AiProvider;
use App\Models\AiModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AiModel>
 */
final class AiModelFactory extends Factory
{
    protected $model = AiModel::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(2, true).' AI Model',
            'provider' => AiProvider::OLLAMA()->value,
            'model_identifier' => 'gemma2:2b',
            'description' => $this->faker->sentence(),
            'endpoint_url' => 'http://209.126.83.125:11434',
            'requires_api_key' => false,
            'api_key' => null,
            'model_config' => json_encode([
                'temperature' => 0.7,
                'max_tokens' => 1000,
                'top_p' => 0.9,
            ]),
            'is_active' => true,
            'is_default' => false,
            'cost_per_1k_tokens' => 0.0,
            'max_context_length' => 4096,
        ];
    }

    public function ollama(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'provider' => AiProvider::OLLAMA()->value,
                'model_identifier' => $this->faker->randomElement(['gemma2:2b', 'llama3.1:8b', 'mistral:latest']),
                'endpoint_url' => 'http://209.126.83.125:11434',
                'requires_api_key' => false,
                'api_key' => null,
                'cost_per_1k_tokens' => 0.0,
            ];
        });
    }

    public function openai(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'provider' => AiProvider::OPENAI()->value,
                'model_identifier' => $this->faker->randomElement(['gpt-4o-mini', 'gpt-3.5-turbo', 'gpt-4']),
                'endpoint_url' => 'https://api.openai.com/v1',
                'requires_api_key' => true,
                'api_key' => 'sk-test-'.$this->faker->lexify('???????????????????????'),
                'cost_per_1k_tokens' => $this->faker->randomFloat(6, 0.00001, 0.001),
            ];
        });
    }

    public function anthropic(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'provider' => AiProvider::ANTHROPIC()->value,
                'model_identifier' => 'claude-3-5-sonnet-20241022',
                'endpoint_url' => 'https://api.anthropic.com/v1',
                'requires_api_key' => true,
                'api_key' => 'sk-ant-test-'.$this->faker->lexify('???????????????????????'),
                'cost_per_1k_tokens' => $this->faker->randomFloat(6, 0.001, 0.01),
            ];
        });
    }

    public function deepseek(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'provider' => AiProvider::DEEPSEEK()->value,
                'model_identifier' => 'deepseek-chat',
                'endpoint_url' => 'https://api.deepseek.com/v1',
                'requires_api_key' => true,
                'api_key' => 'sk-ds-test-'.$this->faker->lexify('???????????????????????'),
                'cost_per_1k_tokens' => $this->faker->randomFloat(6, 0.0001, 0.001),
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
