<?php

declare(strict_types=1);

namespace Tests\Feature\WhatsApp;

use App\Models\AiModel;
use App\Models\User;
use App\Models\WhatsAppAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\CreatesApplication;
use Tests\TestCase;

class AgentPromptValidationTest extends TestCase
{
    use CreatesApplication, RefreshDatabase;

    private User $user;
    private WhatsAppAccount $account;
    private AiModel $aiModel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $this->aiModel = AiModel::factory()->ollama()->create();

        $this->account = WhatsAppAccount::factory()->create([
            'user_id' => $this->user->id,
            'agent_enabled' => false,
            'ai_model_id' => $this->aiModel->id,
            'agent_prompt' => null,
        ]);

        $this->actingAs($this->user);
    }

    #[Test]
    public function it_prevents_agent_activation_without_prompt_via_toggle(): void
    {
        $response = $this->post(route('whatsapp.toggle-ai', $this->account), [
            'enable' => '1',
        ]);

        $response->assertRedirect(route('whatsapp.index'))
            ->assertSessionHas('error', 'Impossible d\'activer l\'agent IA : aucun prompt configuré. Veuillez d\'abord configurer un prompt pour votre agent.');

        $this->account->refresh();
        $this->assertFalse($this->account->agent_enabled);
    }

    #[Test]
    public function it_allows_agent_activation_with_valid_prompt(): void
    {
        // Créer un wallet avec un solde suffisant
        $this->user->wallet()->create(['balance' => 1000]);

        $this->account->update(['agent_prompt' => 'Tu es un assistant utile.']);

        $response = $this->post(route('whatsapp.toggle-ai', $this->account), [
            'enable' => '1',
        ]);

        $response->assertRedirect(route('whatsapp.index'));

        // Vérifions ce qui est dans la session
        if ($response->getSession()->has('error')) {
            $this->fail('Error in session: '.$response->getSession()->get('error'));
        }

        $response->assertSessionHas('success', 'Agent enabled successfully');

        $this->account->refresh();
        $this->assertTrue($this->account->agent_enabled);
    }

    #[Test]
    public function it_prevents_agent_activation_with_empty_prompt(): void
    {
        $this->account->update(['agent_prompt' => '   ']); // Only whitespace

        $response = $this->post(route('whatsapp.toggle-ai', $this->account), [
            'enable' => '1',
        ]);

        $response->assertRedirect(route('whatsapp.index'))
            ->assertSessionHas('error');

        $this->account->refresh();
        $this->assertFalse($this->account->agent_enabled);
    }

    #[Test]
    public function has_ai_agent_returns_false_without_prompt(): void
    {
        $this->account->update([
            'agent_enabled' => true,
            'ai_model_id' => $this->aiModel->id,
            'agent_prompt' => null,
        ]);

        $this->assertFalse($this->account->hasAiAgent());
    }

    #[Test]
    public function has_ai_agent_returns_false_with_empty_prompt(): void
    {
        $this->account->update([
            'agent_enabled' => true,
            'ai_model_id' => $this->aiModel->id,
            'agent_prompt' => '   ', // Only whitespace
        ]);

        $this->assertFalse($this->account->hasAiAgent());
    }

    #[Test]
    public function has_ai_agent_returns_true_with_valid_prompt(): void
    {
        $this->account->update([
            'agent_enabled' => true,
            'ai_model_id' => $this->aiModel->id,
            'agent_prompt' => 'Tu es un assistant utile.',
        ]);

        $this->assertTrue($this->account->hasAiAgent());
    }

    #[Test]
    public function has_valid_prompt_returns_correct_values(): void
    {
        // Test with null prompt
        $this->account->update(['agent_prompt' => null]);
        $this->assertFalse($this->account->hasValidPrompt());

        // Test with empty prompt
        $this->account->update(['agent_prompt' => '']);
        $this->assertFalse($this->account->hasValidPrompt());

        // Test with whitespace only
        $this->account->update(['agent_prompt' => '   ']);
        $this->assertFalse($this->account->hasValidPrompt());

        // Test with valid prompt
        $this->account->update(['agent_prompt' => 'Tu es un assistant utile.']);
        $this->assertTrue($this->account->hasValidPrompt());
    }
}
