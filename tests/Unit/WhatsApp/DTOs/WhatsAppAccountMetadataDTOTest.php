<?php

declare(strict_types=1);

namespace Tests\Unit\WhatsApp\DTOs;

use App\DTOs\WhatsApp\WhatsAppAccountMetadataDTO;
use App\Models\WhatsAppAccount;
use Tests\TestCase;

class WhatsAppAccountMetadataDTOTest extends TestCase
{
    public function test_can_be_instantiated_with_required_properties(): void
    {
        $dto = new WhatsAppAccountMetadataDTO(
            sessionId: 'session_123',
            sessionName: 'Test Session',
            accountId: 1,
            agentEnabled: true
        );

        $this->assertInstanceOf(WhatsAppAccountMetadataDTO::class, $dto);
        $this->assertEquals('session_123', $dto->sessionId);
        $this->assertEquals('Test Session', $dto->sessionName);
        $this->assertEquals(1, $dto->accountId);
        $this->assertTrue($dto->agentEnabled);
    }

    public function test_can_be_created_from_account_model(): void
    {
        $account = WhatsAppAccount::factory()->make([
            'id' => 1,
            'agent_enabled' => true,
            'agent_prompt' => 'Test prompt',
            'contextual_information' => 'Test context'
        ]);

        $dto = WhatsAppAccountMetadataDTO::fromAccount($account, 'session_123', 'Test Session');

        $this->assertInstanceOf(WhatsAppAccountMetadataDTO::class, $dto);
        $this->assertEquals('session_123', $dto->sessionId);
        $this->assertEquals('Test Session', $dto->sessionName);
        $this->assertEquals(1, $dto->accountId);
        $this->assertTrue($dto->agentEnabled);
        $this->assertEquals('Test prompt', $dto->agentPrompt);
        $this->assertEquals('Test context', $dto->contextualInformation);
    }

    public function test_is_agent_active_returns_correct_value(): void
    {
        $enabledDto = new WhatsAppAccountMetadataDTO(
            sessionId: 'session_123',
            sessionName: 'Test Session',
            accountId: 1,
            agentEnabled: true
        );

        $disabledDto = new WhatsAppAccountMetadataDTO(
            sessionId: 'session_123',
            sessionName: 'Test Session',
            accountId: 1,
            agentEnabled: false
        );

        $this->assertTrue($enabledDto->isAgentActive());
        $this->assertFalse($disabledDto->isAgentActive());
    }

    public function test_get_effective_prompt_returns_default_when_null(): void
    {
        $dto = new WhatsAppAccountMetadataDTO(
            sessionId: 'session_123',
            sessionName: 'Test Session',
            accountId: 1,
            agentEnabled: true
        );

        $prompt = $dto->getEffectivePrompt();
        
        $this->assertIsString($prompt);
        $this->assertNotEmpty($prompt);
    }

    public function test_get_effective_prompt_returns_custom_when_set(): void
    {
        $customPrompt = 'Custom prompt for testing';
        
        $dto = new WhatsAppAccountMetadataDTO(
            sessionId: 'session_123',
            sessionName: 'Test Session',
            accountId: 1,
            agentEnabled: true,
            agentPrompt: $customPrompt
        );

        $this->assertEquals($customPrompt, $dto->getEffectivePrompt());
    }
}
