<?php

declare(strict_types=1);

namespace App\DTOs\WhatsApp;

use App\DTOs\BaseDTO;
use App\Models\WhatsAppAccount;
use App\Enums\ResponseTime;

final class WhatsAppAccountMetadataDTO extends BaseDTO
{
    public function __construct(
        public string $sessionId,
        public string $sessionName,
        public int $accountId,
        public bool $agentEnabled,
        public ?string $agentPrompt = null,
        public ?int $aiModelId = null,
        public ResponseTime|string|null $responseTime = null,
        public ?string $contextualInformation = null,
        public ?array $settings = []
    ) {}

    public static function fromAccount(WhatsAppAccount $account, string $sessionId, string $sessionName): self
    {
        return new self(
            sessionId: $sessionId,
            sessionName: $sessionName,
            accountId: $account->id,
            agentEnabled: $account->agent_enabled,
            agentPrompt: $account->agent_prompt,
            aiModelId: $account->ai_model_id,
            responseTime: $account->response_time,
            contextualInformation: $account->contextual_information,
            settings: []
        );
    }

    public static function createDisabled(string $sessionId, string $sessionName): self
    {
        return new self(
            sessionId: $sessionId,
            sessionName: $sessionName,
            accountId: 0, // Pas de compte
            agentEnabled: false, // Agent désactivé
            agentPrompt: null,
            aiModelId: null,
            responseTime: null,
            contextualInformation: null,
            settings: []
        );
    }

    public function isAgentActive(): bool
    {
        return $this->agentEnabled;
    }

    public function getEffectiveAiModelId(): ?int
    {
        return $this->aiModelId;
    }

    public function getEffectivePrompt(): string
    {
        return $this->agentPrompt ?? \App\Models\WhatsAppAccount::getDefaultAgentPrompt();
    }

    public function getEffectiveResponseTime(): ResponseTime|string
    {
        return $this->responseTime ?? ResponseTime::RANDOM();
    }

    public function getResponseTimeDelay(): int
    {
        $responseTimeValue = $this->getEffectiveResponseTime();
        
        if ($responseTimeValue instanceof ResponseTime) {
            return $responseTimeValue->getDelay();
        }
        
        $responseTime = ResponseTime::from($responseTimeValue);
        return $responseTime->getDelay();
    }
}
