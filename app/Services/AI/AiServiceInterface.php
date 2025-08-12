<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\DTOs\AI\AiRequestDTO;
use App\DTOs\AI\AiResponseDTO;
use App\Models\AiModel;

interface AiServiceInterface
{
    public function chat(AiModel $model, AiRequestDTO $request): AiResponseDTO;

    public function validateConfiguration(AiModel $model): bool;

    public function testConnection(AiModel $model): bool;

    public function getRequiredFields(): array;

    public function getDefaultConfig(): array;
}
