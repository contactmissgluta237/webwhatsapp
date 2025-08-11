<?php

declare(strict_types=1);

namespace App\Services\AI;

interface OllamaServiceInterface
{
    public function generateResponse(string $prompt, array $options = []): array;

    public function isHealthy(): bool;

    public function getAvailableModels(): array;
}
