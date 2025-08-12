<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\DTOs\AI\AiRequestDTO;
use App\DTOs\AI\AiResponseDTO;
use App\Models\AiModel;

final class AnthropicService implements AiServiceInterface
{
    public function chat(AiModel $model, AiRequestDTO $request): AiResponseDTO
    {
        // Implémentation Anthropic
        throw new \Exception("Service Anthropic en cours d'implémentation");
    }

    public function validateConfiguration(AiModel $model): bool
    {
        return ! empty($model->api_key) && ! empty($model->model_identifier);
    }

    public function testConnection(AiModel $model): bool
    {
        return false; // À implémenter
    }

    public function getRequiredFields(): array
    {
        return ['api_key', 'model_identifier'];
    }

    public function getDefaultConfig(): array
    {
        return [
            'max_tokens' => 1500,
            'temperature' => 0.7,
            'top_p' => 0.9,
        ];
    }
}
