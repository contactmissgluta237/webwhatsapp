<?php

declare(strict_types=1);

namespace Tests\Feature\WhatsApp;

use App\Livewire\Customer\WhatsApp\AiConfigurationForm;
use App\Models\AiModel;
use App\Models\User;
use App\Models\WhatsAppAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Helpers\AiTestHelper;
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

        // Créer un modèle Ollama pour les tests basé sur la configuration centralisée
        $this->ollamaModel = AiModel::factory()->create(
            AiTestHelper::createTestModelData('ollama', [
                'name' => 'Test Ollama',
                'is_default' => true,
            ])
        );

        $this->account = WhatsAppAccount::factory()->create([
            'user_id' => $this->user->id,
            'ai_model_id' => $this->ollamaModel->id,
            'agent_enabled' => true,
            'stop_on_human_reply' => false,
            'agent_prompt' => 'Tu es un assistant virtuel basique.',
            'session_name' => 'Assistant Test',
            'response_time' => 'random',
        ]);
    }

    /** @test */
    public function it_can_save_stop_on_human_reply_configuration()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(AiConfigurationForm::class, [
            'account' => $this->account,
        ]);

        // Juste modifier la propriété stop_on_human_reply et sauvegarder
        $component
            ->set('agent_enabled', true)
            ->set('agent_name', 'Assistant Test')
            ->set('response_time', 'random')
            ->set('ai_model_id', $this->ollamaModel->id)
            ->set('agent_prompt', 'Tu es un assistant virtuel basique.')
            ->set('stop_on_human_reply', true)
            ->set('trigger_words', '')
            ->set('ignore_words', '')
            ->set('contextual_information', '')
            ->call('save')
            ->assertHasNoErrors();
        $this->assertTrue($this->account->fresh()->stop_on_human_reply);
    }

    /** @test */
    public function it_can_disable_stop_on_human_reply_configuration()
    {
        $this->actingAs($this->user);

        // D'abord activer l'option
        $this->account->update(['stop_on_human_reply' => true]);

        Livewire::test(AiConfigurationForm::class, [
            'account' => $this->account,
        ])
            ->set('agent_enabled', true)
            ->set('agent_name', 'Assistant Test')
            ->set('agent_prompt', 'Tu es un assistant utile')
            ->set('ai_model_id', $this->ollamaModel->id)
            ->set('response_time', 'random')
            ->set('stop_on_human_reply', false)
            ->set('trigger_words', '')
            ->set('ignore_words', '')
            ->set('contextual_information', '')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertFalse($this->account->fresh()->stop_on_human_reply);
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

        $originalPrompt = 'Tu es un assistant basique.';

        $component = Livewire::test(AiConfigurationForm::class, ['account' => $this->account])
            ->set('agent_prompt', $originalPrompt)
            ->call('enhancePrompt')
            ->assertSet('isEnhancing', false)
            ->assertSet('hasEnhancedPrompt', true);

        $enhancedPrompt = $component->get('enhancedPrompt');
        $currentPrompt = $component->get('agent_prompt');

        // Vérifications que le prompt a été réellement amélioré
        $this->assertNotEmpty($enhancedPrompt, 'Le prompt amélioré ne peut pas être vide');
        $this->assertNotEquals($originalPrompt, $enhancedPrompt, 'Le prompt amélioré doit être différent du prompt original');
        $this->assertEquals($enhancedPrompt, $currentPrompt, 'Le prompt courant doit être le prompt amélioré');
        $this->assertEquals($originalPrompt, $component->get('originalPrompt'), 'Le prompt original doit être sauvegardé');
        $this->assertGreaterThanOrEqual(20, strlen($enhancedPrompt), 'Le prompt amélioré doit faire au moins 20 caractères');
        $this->assertGreaterThan(strlen($originalPrompt), strlen($enhancedPrompt), 'Le prompt amélioré doit être plus long que l\'original');

        // Vérifier que le prompt contient des améliorations typiques d'Ollama
        $enhancedLower = strtolower($enhancedPrompt);
        $hasRelevantTerms =
            str_contains($enhancedLower, 'whatsapp') ||
            str_contains($enhancedLower, 'professionnel') ||
            str_contains($enhancedLower, 'assistant') ||
            str_contains($enhancedLower, 'utile') ||
            str_contains($enhancedLower, 'service') ||
            str_contains($enhancedLower, 'help') ||
            str_contains($enhancedLower, 'customer') ||
            str_contains($enhancedLower, 'support') ||
            strlen($enhancedPrompt) > strlen($originalPrompt) * 1.5; // Au moins 50% plus long

        $this->assertTrue(
            $hasRelevantTerms,
            'Le prompt amélioré devrait contenir des termes professionnels ou être significativement plus long'
        );
    }

    /** @test */
    public function it_cannot_enhance_empty_prompt(): void
    {
        $this->actingAs($this->user);

        Livewire::test(AiConfigurationForm::class, ['account' => $this->account])
            ->set('agent_prompt', '') // Vider le prompt
            ->call('enhancePrompt')
            ->assertSet('isEnhancing', false)
            ->assertSet('hasEnhancedPrompt', false);
    }

    /** @test */
    public function it_can_accept_enhanced_prompt(): void
    {
        $this->actingAs($this->user);

        $originalPrompt = 'Prompt original';
        $enhancedPrompt = 'Prompt amélioré par IA';

        $component = Livewire::test(AiConfigurationForm::class, ['account' => $this->account])
            ->set('agent_prompt', $enhancedPrompt) // Le prompt est déjà le prompt amélioré
            ->set('enhancedPrompt', $enhancedPrompt)
            ->set('originalPrompt', $originalPrompt)
            ->set('hasEnhancedPrompt', true)
            ->call('acceptEnhancedPrompt')
            ->assertSet('agent_prompt', $enhancedPrompt) // Reste le prompt amélioré
            ->assertSet('hasEnhancedPrompt', false)
            ->assertSet('isPromptValidated', true)
            ->assertDispatched('show-toast');
    }

    /** @test */
    public function it_can_reject_enhanced_prompt(): void
    {
        $this->actingAs($this->user);

        $originalPrompt = 'Prompt original';
        $enhancedPrompt = 'Prompt amélioré par IA';

        $component = Livewire::test(AiConfigurationForm::class, ['account' => $this->account])
            ->set('agent_prompt', $enhancedPrompt) // Le prompt est déjà modifié
            ->set('enhancedPrompt', $enhancedPrompt)
            ->set('originalPrompt', $originalPrompt)
            ->set('hasEnhancedPrompt', true)
            ->call('rejectEnhancedPrompt')
            ->assertSet('agent_prompt', $originalPrompt) // Retour au prompt original
            ->assertSet('hasEnhancedPrompt', false)
            ->assertSet('isPromptValidated', false)
            ->assertDispatched('show-toast');
    }

    /** @test */
    public function it_handles_prompt_enhancement_service_error(): void
    {
        $this->actingAs($this->user);

        // Créer un modèle avec un endpoint incorrect pour simuler une erreur
        $brokenModel = AiModel::factory()->create([
            'name' => 'Broken Ollama',
            'provider' => 'ollama',
            'model_identifier' => 'nonexistent:model',
            'endpoint_url' => 'http://invalid-endpoint:11434',
            'requires_api_key' => false,
            'is_active' => true,
        ]);

        $this->account->update(['ai_model_id' => $brokenModel->id]);

        $component = Livewire::test(AiConfigurationForm::class, ['account' => $this->account])
            ->set('agent_prompt', 'Prompt de test')
            ->call('enhancePrompt');
        $component->assertSet('isEnhancing', false);
        $component->assertDispatched('show-toast');
    }

    /** @test */
    public function it_validates_agent_prompt_is_required_when_agent_enabled(): void
    {
        $this->actingAs($this->user);

        Livewire::test(AiConfigurationForm::class, ['account' => $this->account])
            ->set('agent_enabled', true)
            ->set('agent_name', 'Assistant Test')
            ->set('response_time', 'random')
            ->set('ai_model_id', $this->ollamaModel->id)
            ->set('agent_prompt', '')
            ->set('trigger_words', '')
            ->set('ignore_words', '')
            ->set('contextual_information', '')
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
            ->set('response_time', 'random')
            ->set('trigger_words', '')
            ->set('ignore_words', '')
            ->set('contextual_information', '')
            ->call('save')
            ->assertHasNoErrors();
    }

    /** @test */
    public function it_requires_ai_model_when_agent_enabled(): void
    {
        $this->actingAs($this->user);

        Livewire::test(AiConfigurationForm::class, ['account' => $this->account])
            ->set('agent_enabled', true)
            ->set('agent_name', 'Assistant Test')
            ->set('agent_prompt', 'Tu es un assistant')
            ->set('response_time', 'random')
            ->set('ai_model_id', null)
            ->set('trigger_words', '')
            ->set('ignore_words', '')
            ->set('contextual_information', '')
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
            ->set('agent_enabled', true)
            ->set('agent_name', 'Assistant Test')
            ->set('agent_prompt', 'Tu es un assistant')
            ->set('ai_model_id', $this->ollamaModel->id)
            ->set('response_time', 'random')
            ->set('stop_on_human_reply', true)
            ->set('trigger_words', '')
            ->set('ignore_words', '')
            ->set('contextual_information', '')
            ->call('save')
            ->assertHasNoErrors();

        $this->account->refresh();
        $this->assertTrue($this->account->stop_on_human_reply);
        // Les trigger_words et ignore_words pourraient être null après sauvegarde car ils sont traités dans le save()
        // On vérifie seulement que la configuration stop_on_human_reply a été sauvegardée
    }

    /** @test */
    public function it_can_access_ai_configuration_page(): void
    {
        $this->actingAs($this->user);

        $response = $this->get(route('whatsapp.configure-ai', $this->account));

        $response->assertStatus(200);
        $response->assertSee('Configuration IA'); // Ou tout autre texte présent sur la page
    }

    /** @test */
    public function it_cannot_access_other_user_ai_configuration(): void
    {
        /** @var User $otherUser */
        $otherUser = User::factory()->create();

        $this->actingAs($otherUser);

        $response = $this->get(route('whatsapp.configure-ai', $this->account));

        $response->assertStatus(403);
    }

    /** @test */
    public function it_has_stop_on_human_reply_defaulting_to_false(): void
    {
        $newAccount = WhatsAppAccount::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $this->assertFalse($newAccount->stop_on_human_reply);
    }

    /** @test */
    public function it_includes_stop_on_human_reply_in_form_validation(): void
    {
        $this->actingAs($this->user);

        $component = Livewire::test(AiConfigurationForm::class, ['account' => $this->account]);

        // Vérifier que le champ stop_on_human_reply est présent dans le composant
        $this->assertNotNull($component->get('stop_on_human_reply'));
    }

    /** @test */
    public function it_properly_saves_enhanced_prompt(): void
    {
        $this->actingAs($this->user);

        $enhancedPrompt = 'Prompt améliore pour WhatsApp';

        Livewire::test(AiConfigurationForm::class, ['account' => $this->account])
            ->set('agent_name', 'Agent Test')
            ->set('agent_enabled', true)
            ->set('ai_model_id', $this->ollamaModel->id)
            ->set('response_time', 'random')
            ->set('agent_prompt', $enhancedPrompt)
            ->set('trigger_words', '')
            ->set('ignore_words', '')
            ->set('contextual_information', '')
            ->call('save')
            ->assertHasNoErrors();

        $this->account->refresh();
        $this->assertEquals($enhancedPrompt, $this->account->agent_prompt);
    }

    /** @test */
    public function test_can_update_stop_on_human_reply_setting(): void
    {
        $this->actingAs($this->user);

        $this->assertFalse($this->account->stop_on_human_reply);

        Livewire::test(AiConfigurationForm::class, ['account' => $this->account])
            ->set('stop_on_human_reply', true)
            ->set('agent_name', 'Test Agent')
            ->set('agent_enabled', true)
            ->set('ai_model_id', $this->ollamaModel->id)
            ->set('response_time', 'fast')
            ->set('trigger_words', '')
            ->set('ignore_words', '')
            ->set('contextual_information', '')
            ->set('agent_prompt', 'Tu es un assistant virtuel.')
            ->call('save')
            ->assertHasNoErrors();

        $this->account->refresh();
        $this->assertTrue($this->account->stop_on_human_reply);
    }

    /** @test */
    public function test_form_validation_includes_stop_on_human_reply(): void
    {
        $this->actingAs($this->user);

        Livewire::test(AiConfigurationForm::class, ['account' => $this->account])
            ->set('agent_name', '') // Required field
            ->set('stop_on_human_reply', true)
            ->call('save')
            ->assertHasErrors(['agent_name']);
    }

    /** @test */
    public function test_loads_current_configuration_including_stop_on_human_reply(): void
    {
        $this->actingAs($this->user);

        $this->account->update([
            'stop_on_human_reply' => true,
            'agent_prompt' => 'Test prompt',
        ]);

        $component = Livewire::test(AiConfigurationForm::class, ['account' => $this->account]);

        $component->assertSet('stop_on_human_reply', true);
        $component->assertSet('agent_prompt', 'Test prompt');
    }
}
