<?php

declare(strict_types=1);

namespace Tests\Feature\WhatsApp;

use App\Contracts\PromptEnhancementInterface;
use App\Models\AiModel;
use App\Models\User;
use App\Models\WhatsAppAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

final class AiConfigurationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private WhatsAppAccount $account;
    private AiModel $ollamaModel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        
        // Créer un modèle Ollama pour les tests
        $this->ollamaModel = AiModel::factory()->create([
            'name' => 'Test Ollama Model',
            'provider' => 'ollama',
            'model_identifier' => 'gemma2:2b',
            'endpoint_url' => 'http://209.126.83.125:11434',
            'requires_api_key' => false,
            'is_active' => true,
            'is_default' => true,
        ]);

        $this->account = WhatsAppAccount::factory()->create([
            'user_id' => $this->user->id,
            'ai_model_id' => $this->ollamaModel->id,
        ]);
    }

    public function test_can_access_ai_configuration_page(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('whatsapp.configure-ai', $this->account));

        $response->assertOk();
        $response->assertSee('Configuration Agent IA');
    }

    public function test_cannot_access_other_user_ai_configuration(): void
    {
        $otherUser = User::factory()->create();
        $otherAccount = WhatsAppAccount::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($this->user)
            ->get(route('whatsapp.configure-ai', $otherAccount));

        $response->assertForbidden();
    }

    public function test_can_update_stop_on_human_reply_setting(): void
    {
        $this->assertFalse($this->account->stop_on_human_reply);

        Livewire::actingAs($this->user)
            ->test('whats-app.ai-configuration-form', ['account' => $this->account])
            ->set('stop_on_human_reply', true)
            ->set('agent_name', 'Test Agent')
            ->set('agent_enabled', true)
            ->set('ai_model_id', $this->ollamaModel->id)
            ->set('response_time', 'fast')
            ->set('trigger_words', '')
            ->set('ignore_words', '')
            ->set('contextual_information', '')
            ->set('agent_prompt', '')
            ->call('save')
            ->assertHasNoErrors();

        $this->account->refresh();
        $this->assertTrue($this->account->stop_on_human_reply);
    }

    public function test_stop_on_human_reply_defaults_to_false(): void
    {
        $newAccount = WhatsAppAccount::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $this->assertFalse($newAccount->stop_on_human_reply);
    }

    public function test_can_enhance_prompt_with_ollama(): void
    {
        // Bind un mock directement dans le container
        $mockService = \Mockery::mock(PromptEnhancementInterface::class);
        $mockService->shouldReceive('enhancePrompt')
            ->once()
            ->with($this->account, 'Prompt simple')
            ->andReturn('Prompt amélioré avec de meilleures instructions pour WhatsApp');
        
        $this->app->instance(PromptEnhancementInterface::class, $mockService);

        Livewire::actingAs($this->user)
            ->test('whats-app.ai-configuration-form', ['account' => $this->account])
            ->set('agent_prompt', 'Prompt simple')
            ->call('enhancePrompt')
            ->assertSet('enhancedPrompt', 'Prompt amélioré avec de meilleures instructions pour WhatsApp')
            ->assertSet('showEnhancementModal', true)
            ->assertSet('isEnhancing', false);
    }

    public function test_cannot_enhance_empty_prompt(): void
    {
        Livewire::actingAs($this->user)
            ->test('whats-app.ai-configuration-form', ['account' => $this->account])
            ->set('agent_prompt', '')
            ->call('enhancePrompt')
            ->assertSet('showEnhancementModal', false)
            ->assertDispatched('show-toast', [
                'type' => 'warning',
                'message' => __('Veuillez d\'abord saisir un prompt à améliorer'),
            ]);
    }

    public function test_can_accept_enhanced_prompt(): void
    {
        $originalPrompt = 'Prompt simple';
        $enhancedPrompt = 'Prompt amélioré';

        Livewire::actingAs($this->user)
            ->test('whats-app.ai-configuration-form', ['account' => $this->account])
            ->set('agent_prompt', $originalPrompt)
            ->set('enhancedPrompt', $enhancedPrompt)
            ->set('showEnhancementModal', true)
            ->call('acceptEnhancedPrompt')
            ->assertSet('agent_prompt', $enhancedPrompt)
            ->assertSet('showEnhancementModal', false)
            ->assertSet('enhancedPrompt', '')
            ->assertDispatched('show-toast', [
                'type' => 'success',
                'message' => __('Prompt amélioré appliqué avec succès'),
            ]);
    }

    public function test_can_reject_enhanced_prompt(): void
    {
        $originalPrompt = 'Prompt simple';
        $enhancedPrompt = 'Prompt amélioré';

        Livewire::actingAs($this->user)
            ->test('whats-app.ai-configuration-form', ['account' => $this->account])
            ->set('agent_prompt', $originalPrompt)
            ->set('enhancedPrompt', $enhancedPrompt)
            ->set('showEnhancementModal', true)
            ->call('rejectEnhancedPrompt')
            ->assertSet('agent_prompt', $originalPrompt) // Reste inchangé
            ->assertSet('showEnhancementModal', false)
            ->assertSet('enhancedPrompt', '');
    }

    public function test_form_validation_includes_stop_on_human_reply(): void
    {
        Livewire::actingAs($this->user)
            ->test('whats-app.ai-configuration-form', ['account' => $this->account])
            ->set('agent_name', '') // Required field
            ->set('stop_on_human_reply', true)
            ->call('save')
            ->assertHasErrors(['agent_name']);
    }

    public function test_loads_current_configuration_including_stop_on_human_reply(): void
    {
        $this->account->update([
            'stop_on_human_reply' => true,
            'agent_prompt' => 'Test prompt',
        ]);

        $component = Livewire::actingAs($this->user)
            ->test('whats-app.ai-configuration-form', ['account' => $this->account]);

        $component->assertSet('stop_on_human_reply', true);
        $component->assertSet('agent_prompt', 'Test prompt');
    }

    public function test_prompt_enhancement_handles_service_errors(): void
    {
        $mockService = \Mockery::mock(PromptEnhancementInterface::class);
        $mockService->shouldReceive('enhancePrompt')
            ->once()
            ->with($this->account, 'Prompt simple')
            ->andThrow(new \Exception('Service indisponible'));
        
        $this->app->instance(PromptEnhancementInterface::class, $mockService);

        Livewire::actingAs($this->user)
            ->test('whats-app.ai-configuration-form', ['account' => $this->account])
            ->set('agent_prompt', 'Prompt simple')
            ->call('enhancePrompt')
            ->assertSet('showEnhancementModal', false)
            ->assertSet('isEnhancing', false)
            ->assertDispatched('show-toast', function ($event, $payload) {
                return $payload['type'] === 'error' && 
                       str_contains($payload['message'], 'Service indisponible');
            });
    }

    public function test_enhanced_prompt_is_properly_saved(): void
    {
        $enhancedPrompt = 'Prompt amélioré pour WhatsApp';

        Livewire::actingAs($this->user)
            ->test('whats-app.ai-configuration-form', ['account' => $this->account])
            ->set('agent_name', 'Agent Test')
            ->set('agent_enabled', true)
            ->set('ai_model_id', $this->ollamaModel->id)
            ->set('agent_prompt', $enhancedPrompt)
            ->set('stop_on_human_reply', true)
            ->set('response_time', 'fast')
            ->set('trigger_words', '')
            ->set('ignore_words', '')
            ->set('contextual_information', '')
            ->call('save')
            ->assertHasNoErrors();

        $this->account->refresh();
        $this->assertEquals($enhancedPrompt, $this->account->agent_prompt);
        $this->assertTrue($this->account->stop_on_human_reply);
    }
}
