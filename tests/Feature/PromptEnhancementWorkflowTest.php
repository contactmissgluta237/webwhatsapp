<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\Customer\WhatsApp\AiConfigurationForm;
use App\Models\AiModel;
use App\Models\User;
use App\Models\WhatsAppAccount;
use App\Services\AI\Contracts\PromptEnhancementInterface;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PromptEnhancementWorkflowTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_shows_initial_state_when_prompt_is_not_empty(): void
    {
        // Arrange - Create test data with explicit values
        $user = User::factory()->create([
            'first_name' => 'Test',
            'last_name' => 'UserPromptEnhancement',
            'email' => 'prompt-enhance@example.com',
        ]);

        $aiModel = AiModel::factory()->create([
            'model_identifier' => 'test-enhancement-gpt',
            'name' => 'Test Enhancement GPT',
            'provider' => 'openai',
            'is_active' => true,
            'is_default' => true,
        ]);

        $account = WhatsAppAccount::factory()->create([
            'user_id' => $user->id,
            'session_id' => 'test_session_enhancement_001',
            'ai_model_id' => $aiModel->id,
            'agent_enabled' => true,
            'stop_on_human_reply' => false,
            'agent_prompt' => 'Tu es un assistant commercial spécialisé dans la vente de produits technologiques.',
            'session_name' => 'Assistant Test Enhancement',
            'response_time' => 'random',
        ]);

        $this->actingAs($user);

        // Act & Assert - Focus on component state rather than HTML
        Livewire::test(AiConfigurationForm::class, ['account' => $account])
            ->assertSet('hasEnhancedPrompt', false)
            ->assertSet('isEnhancing', false)
            ->assertSet('isPromptValidated', false)
            ->assertSet('agent_prompt', 'Tu es un assistant commercial spécialisé dans la vente de produits technologiques.');
    }

    #[Test]
    public function it_shows_initial_state_when_prompt_is_empty(): void
    {
        // Arrange - Create test data with explicit values
        $user = User::factory()->create([
            'first_name' => 'Test',
            'last_name' => 'UserEmptyPrompt',
            'email' => 'empty-prompt@example.com',
        ]);

        $aiModel = AiModel::factory()->create([
            'model_identifier' => 'test-disabled-gpt',
            'name' => 'Test Disabled GPT',
            'provider' => 'anthropic',
            'is_active' => true,
        ]);

        $account = WhatsAppAccount::factory()->create([
            'user_id' => $user->id,
            'session_id' => 'test_session_empty_002',
            'ai_model_id' => $aiModel->id,
            'agent_enabled' => true,
            'agent_prompt' => '', // Empty prompt
            'session_name' => 'Assistant Test Empty',
            'response_time' => 'fast',
        ]);

        $this->actingAs($user);

        // Act & Assert - Focus on component state
        Livewire::test(AiConfigurationForm::class, ['account' => $account])
            ->assertSet('agent_prompt', '')
            ->assertSet('hasEnhancedPrompt', false)
            ->assertSet('isEnhancing', false)
            ->assertSet('isPromptValidated', false);
    }

    #[Test]
    public function it_enhances_prompt_successfully_with_mocked_ai_service(): void
    {
        // Arrange - Create test data with explicit values
        $user = User::factory()->create([
            'first_name' => 'Test',
            'last_name' => 'UserEnhancement',
            'email' => 'enhancement-success@example.com',
        ]);

        $aiModel = AiModel::factory()->create([
            'model_identifier' => 'test-enhance-success',
            'name' => 'Test Enhancement Success',
            'provider' => 'deepseek',
            'is_active' => true,
        ]);

        $account = WhatsAppAccount::factory()->create([
            'user_id' => $user->id,
            'session_id' => 'test_session_enhance_003',
            'ai_model_id' => $aiModel->id,
            'agent_enabled' => true,
            'agent_prompt' => 'Tu es un assistant basique.',
            'session_name' => 'Assistant Test Enhancement Success',
            'response_time' => 'medium',
        ]);

        $originalPrompt = 'Tu es un assistant basique.';
        $expectedEnhancedPrompt = 'Tu es un assistant commercial expert et professionnel, spécialisé dans la vente de produits et services. Tu dois répondre de manière claire, concise et persuasive tout en maintenant un ton amical et professionnel avec tes clients.';

        // Mock the AI enhancement service with simpler expectations
        $mockEnhancementService = Mockery::mock(PromptEnhancementInterface::class);
        $mockEnhancementService->shouldReceive('enhancePrompt')
            ->andReturn($expectedEnhancedPrompt);

        $this->app->instance(PromptEnhancementInterface::class, $mockEnhancementService);
        $this->actingAs($user);

        // Act & Assert
        $component = Livewire::test(AiConfigurationForm::class, ['account' => $account])
            ->set('agent_prompt', $originalPrompt)
            ->call('enhancePrompt')
            ->assertSet('isEnhancing', false)
            ->assertSet('hasEnhancedPrompt', true)
            ->assertSet('originalPrompt', $originalPrompt)
            ->assertSet('enhancedPrompt', $expectedEnhancedPrompt)
            ->assertSet('agent_prompt', $expectedEnhancedPrompt);
    }

    #[Test]
    public function it_prevents_enhancement_of_empty_prompt(): void
    {
        // Arrange - Create test data with explicit values
        $user = User::factory()->create([
            'first_name' => 'Test',
            'last_name' => 'UserEmptyEnhancement',
            'email' => 'empty-enhancement@example.com',
        ]);

        $aiModel = AiModel::factory()->create([
            'model_identifier' => 'test-prevent-empty',
            'name' => 'Test Prevent Empty Enhancement',
            'provider' => 'openai',
            'is_active' => true,
        ]);

        $account = WhatsAppAccount::factory()->create([
            'user_id' => $user->id,
            'session_id' => 'test_session_prevent_004',
            'ai_model_id' => $aiModel->id,
            'agent_enabled' => true,
            'agent_prompt' => '',
            'session_name' => 'Assistant Test Prevent Empty',
            'response_time' => 'slow',
        ]);

        $this->actingAs($user);

        // Act & Assert
        Livewire::test(AiConfigurationForm::class, ['account' => $account])
            ->set('agent_prompt', '')
            ->call('enhancePrompt')
            ->assertSet('hasEnhancedPrompt', false)
            ->assertSet('isEnhancing', false)
            ->assertSet('enhancedPrompt', '')
            ->assertDispatched('show-toast');
    }

    #[Test]
    public function it_accepts_enhanced_prompt_and_updates_state(): void
    {
        // Arrange - Create test data with explicit values
        $user = User::factory()->create([
            'first_name' => 'Test',
            'last_name' => 'UserAcceptEnhancement',
            'email' => 'accept-enhancement@example.com',
        ]);

        $aiModel = AiModel::factory()->create([
            'model_identifier' => 'test-accept-enhanced',
            'name' => 'Test Accept Enhanced',
            'provider' => 'anthropic',
            'is_active' => true,
        ]);

        $account = WhatsAppAccount::factory()->create([
            'user_id' => $user->id,
            'session_id' => 'test_session_accept_005',
            'ai_model_id' => $aiModel->id,
            'agent_enabled' => true,
            'agent_prompt' => 'Tu es un assistant de base.',
            'session_name' => 'Assistant Test Accept',
            'response_time' => 'random',
        ]);

        $originalPrompt = 'Tu es un assistant de base.';
        $enhancedPrompt = 'Tu es un assistant commercial professionnel avec une expertise approfondie dans la relation client et la vente consultative. Tu adaptes ton approche selon les besoins spécifiques de chaque client.';

        // Mock the AI enhancement service
        $mockEnhancementService = Mockery::mock(PromptEnhancementInterface::class);
        $mockEnhancementService->shouldReceive('enhancePrompt')
            ->andReturn($enhancedPrompt);

        $this->app->instance(PromptEnhancementInterface::class, $mockEnhancementService);
        $this->actingAs($user);

        // Act - First enhance, then accept
        $component = Livewire::test(AiConfigurationForm::class, ['account' => $account])
            ->set('agent_prompt', $originalPrompt)
            ->call('enhancePrompt')
            ->assertSet('hasEnhancedPrompt', true)
            ->call('acceptEnhancedPrompt')
            ->assertSet('hasEnhancedPrompt', false)
            ->assertSet('isPromptValidated', true)
            ->assertSet('agent_prompt', $enhancedPrompt)
            ->assertDispatched('show-toast')
            ->assertDispatched('config-changed-live');
    }

    #[Test]
    public function it_rejects_enhanced_prompt_and_restores_original(): void
    {
        // Arrange - Create test data with explicit values
        $user = User::factory()->create([
            'first_name' => 'Test',
            'last_name' => 'UserRejectEnhancement',
            'email' => 'reject-enhancement@example.com',
        ]);

        $aiModel = AiModel::factory()->create([
            'model_identifier' => 'test-reject-enhanced',
            'name' => 'Test Reject Enhanced',
            'provider' => 'deepseek',
            'is_active' => true,
        ]);

        $account = WhatsAppAccount::factory()->create([
            'user_id' => $user->id,
            'session_id' => 'test_session_reject_006',
            'ai_model_id' => $aiModel->id,
            'agent_enabled' => true,
            'agent_prompt' => 'Tu es un assistant original.',
            'session_name' => 'Assistant Test Reject',
            'response_time' => 'fast',
        ]);

        $originalPrompt = 'Tu es un assistant original.';
        $enhancedPrompt = 'Tu es un assistant commercial sophistiqué avec des compétences avancées en communication persuasive et en analyse des besoins clients.';

        // Mock the AI enhancement service
        $mockEnhancementService = Mockery::mock(PromptEnhancementInterface::class);
        $mockEnhancementService->shouldReceive('enhancePrompt')
            ->andReturn($enhancedPrompt);

        $this->app->instance(PromptEnhancementInterface::class, $mockEnhancementService);
        $this->actingAs($user);

        // Act - First enhance, then reject
        Livewire::test(AiConfigurationForm::class, ['account' => $account])
            ->set('agent_prompt', $originalPrompt)
            ->call('enhancePrompt')
            ->assertSet('hasEnhancedPrompt', true)
            ->assertSet('agent_prompt', $enhancedPrompt)
            ->call('rejectEnhancedPrompt')
            ->assertSet('agent_prompt', $originalPrompt)
            ->assertSet('hasEnhancedPrompt', false)
            ->assertSet('isPromptValidated', false)
            ->assertDispatched('config-changed-live');
    }

    #[Test]
    public function it_clears_prompt_completely_and_resets_enhancement_state(): void
    {
        // Arrange - Create test data with explicit values
        $user = User::factory()->create([
            'first_name' => 'Test',
            'last_name' => 'UserClearPrompt',
            'email' => 'clear-prompt@example.com',
        ]);

        $aiModel = AiModel::factory()->create([
            'model_identifier' => 'test-clear-prompt',
            'name' => 'Test Clear Prompt',
            'provider' => 'openai',
            'is_active' => true,
        ]);

        $account = WhatsAppAccount::factory()->create([
            'user_id' => $user->id,
            'session_id' => 'test_session_clear_007',
            'ai_model_id' => $aiModel->id,
            'agent_enabled' => true,
            'agent_prompt' => 'Tu es un assistant que nous allons supprimer.',
            'session_name' => 'Assistant Test Clear',
            'response_time' => 'medium',
        ]);

        $this->actingAs($user);

        // Act & Assert
        Livewire::test(AiConfigurationForm::class, ['account' => $account])
            ->assertSet('agent_prompt', 'Tu es un assistant que nous allons supprimer.')
            ->call('clearPrompt')
            ->assertSet('agent_prompt', '')
            ->assertSet('hasEnhancedPrompt', false)
            ->assertSet('isPromptValidated', false)
            ->assertSet('enhancedPrompt', '')
            ->assertSet('originalPrompt', '')
            ->assertDispatched('show-toast')
            ->assertDispatched('config-changed-live');
    }

    #[Test]
    public function it_resets_enhancement_state_when_prompt_is_manually_modified(): void
    {
        // Arrange - Create test data with explicit values
        $user = User::factory()->create([
            'first_name' => 'Test',
            'last_name' => 'UserManualModification',
            'email' => 'manual-mod@example.com',
        ]);

        $aiModel = AiModel::factory()->create([
            'model_identifier' => 'test-manual-mod',
            'name' => 'Test Manual Modification',
            'provider' => 'anthropic',
            'is_active' => true,
        ]);

        $account = WhatsAppAccount::factory()->create([
            'user_id' => $user->id,
            'session_id' => 'test_session_manual_008',
            'ai_model_id' => $aiModel->id,
            'agent_enabled' => true,
            'agent_prompt' => 'Tu es un assistant initial.',
            'session_name' => 'Assistant Test Manual Mod',
            'response_time' => 'slow',
        ]);

        $originalPrompt = 'Tu es un assistant initial.';
        $enhancedPrompt = 'Tu es un assistant commercial expert avec une approche consultative et personnalisée pour chaque interaction client.';
        $manuallyModifiedPrompt = 'Tu es un assistant modifié manuellement par l\'utilisateur.';

        // Mock the AI enhancement service
        $mockEnhancementService = Mockery::mock(PromptEnhancementInterface::class);
        $mockEnhancementService->shouldReceive('enhancePrompt')
            ->andReturn($enhancedPrompt);

        $this->app->instance(PromptEnhancementInterface::class, $mockEnhancementService);
        $this->actingAs($user);

        // Act & Assert
        Livewire::test(AiConfigurationForm::class, ['account' => $account])
            ->set('agent_prompt', $originalPrompt)
            ->call('enhancePrompt')
            ->assertSet('hasEnhancedPrompt', true)
            ->call('acceptEnhancedPrompt')
            ->assertSet('isPromptValidated', true)
            ->set('agent_prompt', $manuallyModifiedPrompt) // Manual modification
            ->assertSet('isPromptValidated', false) // Should reset validation state
            ->assertDispatched('config-changed-live');
    }

    #[Test]
    public function it_handles_ai_service_errors_gracefully(): void
    {
        // Arrange - Create test data with explicit values
        $user = User::factory()->create([
            'first_name' => 'Test',
            'last_name' => 'UserErrorHandling',
            'email' => 'error-handling@example.com',
        ]);

        $aiModel = AiModel::factory()->create([
            'model_identifier' => 'test-error-handling',
            'name' => 'Test Error Handling',
            'provider' => 'deepseek',
            'is_active' => true,
        ]);

        $account = WhatsAppAccount::factory()->create([
            'user_id' => $user->id,
            'session_id' => 'test_session_error_009',
            'ai_model_id' => $aiModel->id,
            'agent_enabled' => true,
            'agent_prompt' => 'Tu es un assistant pour tester les erreurs.',
            'session_name' => 'Assistant Test Error Handling',
            'response_time' => 'random',
        ]);

        // Mock the AI enhancement service to throw an exception
        $mockEnhancementService = Mockery::mock(PromptEnhancementInterface::class);
        $mockEnhancementService->shouldReceive('enhancePrompt')
            ->andThrow(new Exception('Service IA temporairement indisponible'));

        $this->app->instance(PromptEnhancementInterface::class, $mockEnhancementService);
        $this->actingAs($user);

        // Act & Assert
        Livewire::test(AiConfigurationForm::class, ['account' => $account])
            ->set('agent_prompt', 'Tu es un assistant pour tester les erreurs.')
            ->call('enhancePrompt')
            ->assertSet('hasEnhancedPrompt', false)
            ->assertSet('isEnhancing', false)
            ->assertSet('enhancedPrompt', '');
    }

    #[Test]
    public function it_dispatches_correct_events_during_enhancement_workflow(): void
    {
        // Arrange - Create test data with explicit values
        $user = User::factory()->create([
            'first_name' => 'Test',
            'last_name' => 'UserEventDispatch',
            'email' => 'event-dispatch@example.com',
        ]);

        $aiModel = AiModel::factory()->create([
            'model_identifier' => 'test-event-dispatch',
            'name' => 'Test Event Dispatch',
            'provider' => 'openai',
            'is_active' => true,
        ]);

        $account = WhatsAppAccount::factory()->create([
            'user_id' => $user->id,
            'session_id' => 'test_session_events_010',
            'ai_model_id' => $aiModel->id,
            'agent_enabled' => true,
            'agent_prompt' => 'Tu es un assistant pour tester les événements.',
            'session_name' => 'Assistant Test Events',
            'response_time' => 'fast',
        ]);

        $originalPrompt = 'Tu es un assistant pour tester les événements.';
        $enhancedPrompt = 'Tu es un assistant commercial spécialisé dans l\'analyse comportementale des événements clients et l\'optimisation des interactions.';

        // Mock the AI enhancement service
        $mockEnhancementService = Mockery::mock(PromptEnhancementInterface::class);
        $mockEnhancementService->shouldReceive('enhancePrompt')
            ->andReturn($enhancedPrompt);

        $this->app->instance(PromptEnhancementInterface::class, $mockEnhancementService);
        $this->actingAs($user);

        // Act & Assert - Test complete workflow events
        Livewire::test(AiConfigurationForm::class, ['account' => $account])
            ->set('agent_prompt', $originalPrompt)
            ->assertDispatched('config-changed-live')
            ->call('enhancePrompt')
            ->assertDispatched('config-changed-live')
            ->call('acceptEnhancedPrompt')
            ->assertDispatched('show-toast')
            ->assertDispatched('config-changed-live');
    }

    #[Test]
    public function it_validates_prompt_length_limits_during_enhancement(): void
    {
        // Arrange - Create test data with explicit values
        $user = User::factory()->create([
            'first_name' => 'Test',
            'last_name' => 'UserLengthValidation',
            'email' => 'length-validation@example.com',
        ]);

        $aiModel = AiModel::factory()->create([
            'model_identifier' => 'test-length-validation',
            'name' => 'Test Length Validation',
            'provider' => 'anthropic',
            'is_active' => true,
        ]);

        $account = WhatsAppAccount::factory()->create([
            'user_id' => $user->id,
            'session_id' => 'test_session_length_011',
            'ai_model_id' => $aiModel->id,
            'agent_enabled' => true,
            'agent_prompt' => 'Court prompt.',
            'session_name' => 'Assistant Test Length',
            'response_time' => 'medium',
        ]);

        $shortPrompt = 'Court prompt.';
        $properEnhancedPrompt = 'Tu es un assistant commercial professionnel qui excelle dans la communication client, l\'analyse des besoins et la proposition de solutions adaptées.';

        // Mock the AI enhancement service
        $mockEnhancementService = Mockery::mock(PromptEnhancementInterface::class);
        $mockEnhancementService->shouldReceive('enhancePrompt')
            ->once()
            ->andReturn($properEnhancedPrompt);

        $this->app->instance(PromptEnhancementInterface::class, $mockEnhancementService);
        $this->actingAs($user);

        // Act & Assert
        $component = Livewire::test(AiConfigurationForm::class, ['account' => $account])
            ->set('agent_prompt', $shortPrompt)
            ->call('enhancePrompt')
            ->assertSet('enhancedPrompt', $properEnhancedPrompt);

        // Verify enhanced prompt is longer than original
        $enhancedLength = strlen($component->get('enhancedPrompt'));
        $originalLength = strlen($shortPrompt);
        $this->assertGreaterThan($originalLength, $enhancedLength);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
