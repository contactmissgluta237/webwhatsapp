<?php

declare(strict_types=1);

namespace Tests\Feature\WhatsApp;

use App\Livewire\WhatsApp\AiConfigurationForm;
use App\Models\AiModel;
use App\Models\User;
use App\Models\WhatsAppAccount;
use App\Services\AI\PromptEnhancementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

final class AiConfigurationEnhancementsTest extends TestCase
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
            'name' => 'Test Ollama',
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
            'agent_enabled' => true,
            'stop_on_human_reply' => false,
            'agent_prompt' => 'Tu es un assistant virtuel basique.',
        ]);
    }

    /** @test */
    public function it_can_save_stop_on_human_reply_configuration(): void
    {
        $this->actingAs($this->user);

        Livewire::test(AiConfigurationForm::class, ['account' => $this->account])
            ->set('stop_on_human_reply', true)
            ->set('agent_name', 'Agent Test')
            ->set('agent_enabled', true)
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('ai-config-saved');

        $this->account->refresh();
        $this->assertTrue($this->account->stop_on_human_reply);
    }

    /** @test */
    public function it_can_disable_stop_on_human_reply_configuration(): void
    {
        // Démarrer avec stop_on_human_reply activé
        $this->account->update(['stop_on_human_reply' => true]);
        
        $this->actingAs($this->user);

        Livewire::test(AiConfigurationForm::class, ['account' => $this->account])
            ->set('stop_on_human_reply', false)
            ->call('save')
            ->assertHasNoErrors();

        $this->account->refresh();
        $this->assertFalse($this->account->stop_on_human_reply);
    }

    /** @test */
    public function it_loads_stop_on_human_reply_from_existing_configuration(): void
    {
        $this->account->update(['stop_on_human_reply' => true]);
        
        $this->actingAs($this->user);

        $component = Livewire::test(AiConfigurationForm::class, ['account' => $this->account]);
        
        $this->assertTrue($component->get('stop_on_human_reply'));
    }

    /** @test */
    public function it_can_enhance_prompt_with_ollama(): void
    {
        $this->actingAs($this->user);
        
        // Mock du service d'amélioration
        $this->mock(PromptEnhancementService::class, function ($mock) {
            $mock->shouldReceive('enhancePrompt')
                ->once()
                ->with($this->account, 'Tu es un assistant virtuel basique.')
                ->andReturn('Tu es un assistant virtuel professionnel spécialisé dans le support client WhatsApp. Tu réponds de manière claire, concise et amicale. Tu utilises des emojis appropriés et tu guides les utilisateurs vers les solutions adaptées.');
        });

        Livewire::test(AiConfigurationForm::class, ['account' => $this->account])
            ->call('enhancePrompt')
            ->assertSet('isEnhancing', false)
            ->assertSet('showEnhancementModal', true)
            ->assertSet('enhancedPrompt', 'Tu es un assistant virtuel professionnel spécialisé dans le support client WhatsApp. Tu réponds de manière claire, concise et amicale. Tu utilises des emojis appropriés et tu guides les utilisateurs vers les solutions adaptées.');
    }

    /** @test */
    public function it_cannot_enhance_empty_prompt(): void
    {
        $this->account->update(['agent_prompt' => '']);
        
        $this->actingAs($this->user);

        Livewire::test(AiConfigurationForm::class, ['account' => $this->account])
            ->call('enhancePrompt')
            ->assertHasErrors(['agent_prompt'])
            ->assertSet('isEnhancing', false)
            ->assertSet('showEnhancementModal', false);
    }

    /** @test */
    public function it_can_accept_enhanced_prompt(): void
    {
        $this->actingAs($this->user);
        
        $enhancedPrompt = 'Prompt amélioré par IA';

        Livewire::test(AiConfigurationForm::class, ['account' => $this->account])
            ->set('enhancedPrompt', $enhancedPrompt)
            ->set('showEnhancementModal', true)
            ->call('acceptEnhancedPrompt')
            ->assertSet('agent_prompt', $enhancedPrompt)
            ->assertSet('showEnhancementModal', false)
            ->assertDispatched('prompt-enhanced');
    }

    /** @test */
    public function it_can_reject_enhanced_prompt(): void
    {
        $this->actingAs($this->user);
        
        $originalPrompt = $this->account->agent_prompt;
        $enhancedPrompt = 'Prompt amélioré par IA';

        Livewire::test(AiConfigurationForm::class, ['account' => $this->account])
            ->set('enhancedPrompt', $enhancedPrompt)
            ->set('showEnhancementModal', true)
            ->call('rejectEnhancedPrompt')
            ->assertSet('agent_prompt', $originalPrompt)
            ->assertSet('showEnhancementModal', false)
            ->assertSet('enhancedPrompt', '');
    }

    /** @test */
    public function it_handles_prompt_enhancement_service_error(): void
    {
        $this->actingAs($this->user);
        
        // Mock du service qui lance une exception
        $this->mock(PromptEnhancementService::class, function ($mock) {
            $mock->shouldReceive('enhancePrompt')
                ->once()
                ->andThrow(new \Exception('Erreur du service IA'));
        });

        Livewire::test(AiConfigurationForm::class, ['account' => $this->account])
            ->call('enhancePrompt')
            ->assertSet('isEnhancing', false)
            ->assertSet('showEnhancementModal', false)
            ->assertDispatched('ai-error', message: 'Impossible d\'améliorer le prompt: Erreur du service IA');
    }

    /** @test */
    public function it_validates_agent_prompt_is_required_when_agent_enabled(): void
    {
        $this->actingAs($this->user);

        Livewire::test(AiConfigurationForm::class, ['account' => $this->account])
            ->set('agent_enabled', true)
            ->set('agent_prompt', '')
            ->call('save')
            ->assertHasErrors(['agent_prompt']);
    }

    /** @test */
    public function it_allows_empty_prompt_when_agent_disabled(): void
    {
        $this->actingAs($this->user);

        Livewire::test(AiConfigurationForm::class, ['account' => $this->account])
            ->set('agent_enabled', false)
            ->set('agent_prompt', '')
            ->set('agent_name', 'Agent Test')
            ->call('save')
            ->assertHasNoErrors();
    }

    /** @test */
    public function it_requires_ai_model_when_agent_enabled(): void
    {
        $this->actingAs($this->user);

        Livewire::test(AiConfigurationForm::class, ['account' => $this->account])
            ->set('agent_enabled', true)
            ->set('ai_model_id', null)
            ->call('save')
            ->assertHasErrors(['ai_model_id']);
    }

    /** @test */
    public function only_account_owner_can_configure_ai(): void
    {
        /** @var User $otherUser */
        $otherUser = User::factory()->create();
        
        $this->actingAs($otherUser);

        $this->get(route('whatsapp.configure-ai', $this->account))
            ->assertStatus(403);
    }

    /** @test */
    public function it_preserves_other_configuration_when_updating_stop_on_human_reply(): void
    {
        $this->actingAs($this->user);
        
        $originalTriggerWords = ['aide', 'support'];
        $originalIgnoreWords = ['stop', 'humain'];
        
        $this->account->update([
            'trigger_words' => $originalTriggerWords,
            'ignore_words' => $originalIgnoreWords,
            'response_time' => 'immediate',
        ]);

        Livewire::test(AiConfigurationForm::class, ['account' => $this->account])
            ->set('stop_on_human_reply', true)
            ->call('save')
            ->assertHasNoErrors();

        $this->account->refresh();
        $this->assertTrue($this->account->stop_on_human_reply);
        $this->assertEquals($originalTriggerWords, $this->account->trigger_words);
        $this->assertEquals($originalIgnoreWords, $this->account->ignore_words);
        $this->assertEquals('immediate', $this->account->response_time);
    }
}
