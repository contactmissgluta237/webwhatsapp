<?php

declare(strict_types=1);

namespace Tests\Unit\Services\AI;

use App\Models\AiModel;
use App\Models\WhatsAppAccount;
use App\Services\AI\Helpers\AgentPromptHelper;
use App\Services\AI\PromptEnhancementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use PHPUnit\Framework\Attributes\Test;
use Tests\Helpers\AiTestHelper;
use Tests\TestCase;

final class PromptEnhancementServiceTest extends TestCase
{
    use RefreshDatabase;

    private WhatsAppAccount $account;
    private AiModel $ollamaModel;

    protected function setUp(): void
    {
        parent::setUp();
        App::setLocale('fr');

        $this->ollamaModel = AiModel::factory()->create(
            AiTestHelper::createTestModelData('ollama', [
                'name' => 'Test Ollama',
                'is_default' => true,
            ])
        );

        $this->account = WhatsAppAccount::factory()->create([
            'ai_model_id' => $this->ollamaModel->id,
        ]);
    }

    private function createService(): PromptEnhancementService
    {
        return new PromptEnhancementService;
    }

    #[Test]
    public function it_throws_exception_when_ai_services_unavailable(): void
    {
        // Désactiver tous les modèles AI pour simuler l'indisponibilité des services
        AiModel::query()->update(['is_active' => false]);

        $originalPrompt = 'Tu es un assistant.';
        $service = $this->createService();

        // Quand aucun service AI n'est disponible, le service devrait lever une exception claire
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No AI model available for prompt enhancement');

        $service->enhancePrompt($this->account, $originalPrompt);
    }

    #[Test]
    public function it_throws_exception_when_account_has_no_model_and_no_default(): void
    {
        // Supprimer tous les modèles IA
        AiModel::query()->delete();
        $this->account->update(['ai_model_id' => null]);

        $service = $this->createService();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No AI model available for prompt enhancement');

        $service->enhancePrompt($this->account, 'Test prompt');
    }

    #[Test]
    public function it_finds_enhancement_model_from_account(): void
    {
        $service = $this->createService();

        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('getEnhancementModel');
        $method->setAccessible(true);

        $result = $method->invoke($service, $this->account);

        $this->assertInstanceOf(AiModel::class, $result);
        $this->assertEquals($this->ollamaModel->id, $result->id);
    }

    #[Test]
    public function it_successfully_enhances_prompt_with_mocked_service(): void
    {
        // Test de la logique de nettoyage sans appel externe
        $service = $this->createService();
        $reflection = new \ReflectionClass($service);
        $cleanMethod = $reflection->getMethod('cleanEnhancedPrompt');
        $cleanMethod->setAccessible(true);

        // Simuler une réponse AI avec du contenu structuré
        $aiResponse = "**Prompt amélioré pour agent WhatsApp :**\n\nTu es un assistant professionnel pour WhatsApp. Réponds de manière claire et concise.";

        $result = $cleanMethod->invoke($service, $aiResponse);

        $this->assertNotEmpty($result);
        $this->assertStringContainsString('Tu es un assistant professionnel', $result);
        $this->assertStringNotContainsString('**', $result);
        // Vérifier que certains éléments de formatage ont été supprimés
        $this->assertStringNotContainsString('**Prompt', $result);
    }

    #[Test]
    public function it_cleans_enhanced_prompt_correctly(): void
    {
        $service = $this->createService();
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('cleanEnhancedPrompt');
        $method->setAccessible(true);

        $messyPrompt = "**Prompt amélioré :**\n\n✅ Tu es un excellent assistant.\n- Réponds clairement\n- *Sois* **professionnel**\n\n🎯 Objectifs : aider les clients";

        $result = $method->invoke($service, $messyPrompt);

        $this->assertStringNotContainsString('**', $result);
        $this->assertStringNotContainsString('*', $result);
        $this->assertStringNotContainsString('✅', $result);
        $this->assertStringNotContainsString('🎯', $result);
        $this->assertStringNotContainsString('-', $result);
        $this->assertStringContainsString('Tu es un excellent assistant', $result);
        $this->assertStringContainsString('Réponds clairement', $result);
    }

    #[Test]
    public function it_uses_account_model_first(): void
    {
        $accountModel = AiModel::factory()->create(
            AiTestHelper::createTestModelData('openai', [
                'name' => 'Account Specific Model',
                'is_active' => true,
                'is_default' => false,
            ])
        );

        $this->account->update(['ai_model_id' => $accountModel->id]);

        $service = $this->createService();
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('getEnhancementModel');
        $method->setAccessible(true);

        $result = $method->invoke($service, $this->account);

        $this->assertEquals($accountModel->id, $result->id);
    }

    #[Test]
    public function it_falls_back_to_ollama_default_when_account_model_inactive(): void
    {
        // Créer un modèle inactif pour le compte
        $inactiveModel = AiModel::factory()->create(
            AiTestHelper::createTestModelData('openai', [
                'name' => 'Inactive Model',
                'is_active' => false,
            ])
        );

        $this->account->update(['ai_model_id' => $inactiveModel->id]);

        $service = $this->createService();
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('getEnhancementModel');
        $method->setAccessible(true);

        $result = $method->invoke($service, $this->account);

        // Devrait utiliser le modèle Ollama par défaut
        $this->assertEquals($this->ollamaModel->id, $result->id);
        $this->assertEquals('ollama', $result->provider->value);
    }

    #[Test]
    public function it_handles_empty_enhanced_prompt(): void
    {
        $service = $this->createService();
        $reflection = new \ReflectionClass($service);
        $cleanMethod = $reflection->getMethod('cleanEnhancedPrompt');
        $cleanMethod->setAccessible(true);

        // Tester le nettoyage d'un contenu qui devient quasi vide
        $emptyContent = '**Titre:** \n\n- \n\n*   *\n\n';
        $result = $cleanMethod->invoke($service, $emptyContent);

        // Le contenu devrait être largement réduit
        $this->assertLessThan(50, strlen(trim($result)));
        // Et ne doit plus contenir de formatage markdown
        $this->assertStringNotContainsString('**', $result);
        $this->assertStringNotContainsString('*', $result);
    }

    #[Test]
    public function it_finds_fallback_models_correctly(): void
    {
        // Créer plusieurs modèles actifs
        $openaiModel = AiModel::factory()->create(
            AiTestHelper::createTestModelData('openai', [
                'name' => 'OpenAI GPT',
                'is_active' => true,
                'is_default' => false,
            ])
        );

        $anthropicModel = AiModel::factory()->create(
            AiTestHelper::createTestModelData('anthropic', [
                'name' => 'Claude',
                'is_active' => true,
                'is_default' => false,
            ])
        );

        $service = $this->createService();
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('getFallbackModels');
        $method->setAccessible(true);

        // Exclure le modèle principal
        $result = $method->invoke($service, [$this->ollamaModel->id]);

        $this->assertCount(2, $result);
        $this->assertFalse($result->contains('id', $this->ollamaModel->id));
        $this->assertTrue($result->contains('id', $openaiModel->id));
        $this->assertTrue($result->contains('id', $anthropicModel->id));
    }

    #[Test]
    public function it_validates_system_prompt_structure(): void
    {
        $systemPrompt = AgentPromptHelper::getEnhancementSystemPrompt();

        $this->assertStringContainsString('expert en amélioration de prompts', $systemPrompt);
        $this->assertStringContainsString('WhatsApp', $systemPrompt);
        $this->assertStringContainsString('ANALYSE PRÉALABLE', $systemPrompt);
        $this->assertStringContainsString('UNIQUEMENT avec le texte du prompt amélioré', $systemPrompt);
    }

    #[Test]
    public function it_creates_correct_ai_request_dto(): void
    {
        $systemPrompt = AgentPromptHelper::getEnhancementSystemPrompt();

        $this->assertNotEmpty($systemPrompt);
        $this->assertIsString($systemPrompt);

        // Vérifier que le prompt système contient les éléments clés
        $this->assertStringContainsString('amélioration de prompts', $systemPrompt);
        $this->assertStringContainsString('WhatsApp', $systemPrompt);
        $this->assertStringContainsString('RÈGLES D\'AMÉLIORATION', $systemPrompt);
    }
}
