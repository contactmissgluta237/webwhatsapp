<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Contracts\PromptEnhancementInterface;
use App\Livewire\WhatsApp\AiConfigurationForm;
use App\Models\AiModel;
use App\Models\User;
use App\Models\WhatsAppAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\Helpers\AiTestHelper;
use Tests\TestCase;

class PromptEnhancementWorkflowTest extends TestCase
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
            'agent_prompt' => 'Prompt de base',
            'session_name' => 'Assistant Test',
            'response_time' => 'random',
        ]);
    }

    #[Test]
    public function it_shows_enhance_button_when_prompt_is_not_empty(): void
    {
        $this->actingAs($this->user);

        Livewire::test(AiConfigurationForm::class, ['account' => $this->account])
            ->assertSee('Améliorer le prompt')
            ->assertDontSee('Accepter')
            ->assertDontSee('Annuler');
    }

    #[Test]
    public function it_hides_enhance_button_when_prompt_is_empty(): void
    {
        $this->actingAs($this->user);

        $this->account->update(['agent_prompt' => '']);

        Livewire::test(AiConfigurationForm::class, ['account' => $this->account])
            ->set('agent_prompt', '')
            ->assertSee('Améliorer le prompt')
            ->assertSeeHtml('disabled');
    }

    #[Test]
    public function it_enhances_prompt_and_shows_validation_buttons(): void
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
        $this->assertNotEmpty($enhancedPrompt);
        $this->assertNotEquals($originalPrompt, $enhancedPrompt);
        $this->assertEquals($enhancedPrompt, $currentPrompt);
        $this->assertEquals($originalPrompt, $component->get('originalPrompt'));
        $this->assertGreaterThanOrEqual(20, strlen($enhancedPrompt));
    }

    #[Test]
    public function it_accepts_enhanced_prompt_and_saves_it(): void
    {
        $this->actingAs($this->user);

        $originalPrompt = 'Tu es un assistant basique.';

        // D'abord améliorer le prompt
        $component = Livewire::test(AiConfigurationForm::class, ['account' => $this->account])
            ->set('agent_prompt', $originalPrompt)
            ->call('enhancePrompt')
            ->assertSet('hasEnhancedPrompt', true);

        $enhancedPrompt = $component->get('enhancedPrompt');

        // Accepter l'amélioration
        $component->call('acceptEnhancedPrompt')
            ->assertSet('hasEnhancedPrompt', false)
            ->assertSet('isPromptValidated', true)
            ->assertSet('agent_prompt', $enhancedPrompt);
    }

    #[Test]
    public function it_rejects_enhanced_prompt_and_restores_original(): void
    {
        $this->actingAs($this->user);

        // Setup enhanced state manually
        $component = Livewire::test(AiConfigurationForm::class, ['account' => $this->account]);
        $component->set('hasEnhancedPrompt', true)
            ->set('enhancedPrompt', 'Prompt amélioré')
            ->set('agent_prompt', 'Prompt amélioré')
            ->set('originalPrompt', 'Prompt de base');

        // Reject enhancement
        $component->call('rejectEnhancedPrompt')
            ->assertSet('agent_prompt', 'Prompt de base')
            ->assertSet('hasEnhancedPrompt', false)
            ->assertSet('isPromptValidated', false);
    }

    #[Test]
    public function it_resets_enhancement_state_when_prompt_is_manually_modified(): void
    {
        $this->actingAs($this->user);

        // Setup validated state
        $component = Livewire::test(AiConfigurationForm::class, ['account' => $this->account]);
        $component->set('isPromptValidated', true)
            ->set('hasEnhancedPrompt', false)
            ->set('enhancedPrompt', 'Prompt amélioré')
            ->set('agent_prompt', 'Prompt amélioré');

        // Manually modify prompt
        $component->set('agent_prompt', 'Nouveau prompt modifié manuellement')
            ->assertSet('isPromptValidated', false);
    }

    #[Test]
    public function it_cannot_enhance_empty_prompt(): void
    {
        $this->actingAs($this->user);

        $component = Livewire::test(AiConfigurationForm::class, ['account' => $this->account])
            ->set('agent_prompt', '')
            ->call('enhancePrompt')
            ->assertSet('hasEnhancedPrompt', false)
            ->assertSet('isEnhancing', false);
    }

    #[Test]
    public function it_handles_enhancement_service_errors_gracefully(): void
    {
        $this->actingAs($this->user);

        // Mock du service qui lance une exception
        $mockService = Mockery::mock(PromptEnhancementInterface::class);
        $mockService->shouldReceive('enhancePrompt')
            ->once()
            ->andThrow(new \Exception('Service indisponible'));

        $this->app->instance(PromptEnhancementInterface::class, $mockService);

        $component = Livewire::test(AiConfigurationForm::class, ['account' => $this->account]);
        $component->call('enhancePrompt')
            ->assertSet('hasEnhancedPrompt', false)
            ->assertSet('isEnhancing', false);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
