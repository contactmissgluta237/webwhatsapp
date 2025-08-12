<?php

declare(strict_types=1);

namespace Tests\Unit\Services\AI;

use App\DTOs\AI\AiRequestDTO;
use App\DTOs\AI\AiResponseDTO;
use App\Models\AiModel;
use App\Models\WhatsAppAccount;
use App\Services\AI\AiServiceInterface;
use App\Services\AI\PromptEnhancementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PromptEnhancementServiceTest extends TestCase
{
    use RefreshDatabase;

    private PromptEnhancementService $service;
    private WhatsAppAccount $account;
    private AiModel $ollamaModel;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = new PromptEnhancementService();
        
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
            'ai_model_id' => $this->ollamaModel->id,
        ]);
    }

    /** @test */
    public function it_enhances_prompt_using_configured_model(): void
    {
        $originalPrompt = 'Tu es un assistant.';
        $enhancedPrompt = 'Tu es un assistant professionnel spécialisé dans le support client.';
        
        // Mock du service IA
        $mockAiService = $this->mock(AiServiceInterface::class);
        $mockAiService->shouldReceive('chat')
            ->once()
            ->with(
                $this->ollamaModel,
                \Mockery::type(AiRequestDTO::class)
            )
            ->andReturn(new AiResponseDTO(
                content: $enhancedPrompt,
                tokensUsed: 50,
            ));
        
        $this->app->bind(AiServiceInterface::class, fn() => $mockAiService);
        
        $result = $this->service->enhancePrompt($this->account, $originalPrompt);
        
        $this->assertEquals($enhancedPrompt, $result);
    }

    /** @test */
    public function it_uses_default_ollama_model_when_account_has_no_model(): void
    {
        $this->account->update(['ai_model_id' => null]);
        
        $originalPrompt = 'Salut';
        $enhancedPrompt = 'Salut ! Je suis votre assistant virtuel.';
        
        // Mock du service IA
        $mockAiService = $this->mock(AiServiceInterface::class);
        $mockAiService->shouldReceive('chat')
            ->once()
            ->andReturn(new AiResponseDTO(
                content: $enhancedPrompt,
                tokensUsed: 30,
            ));
        
        $this->app->bind(AiServiceInterface::class, fn() => $mockAiService);
        
        $result = $this->service->enhancePrompt($this->account, $originalPrompt);
        
        $this->assertEquals($enhancedPrompt, $result);
    }

    /** @test */
    public function it_throws_exception_when_no_ai_model_available(): void
    {
        // Supprimer tous les modèles IA
        AiModel::query()->delete();
        
        $this->account->update(['ai_model_id' => null]);
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Aucun modèle IA disponible pour l\'amélioration du prompt');
        
        $this->service->enhancePrompt($this->account, 'Test prompt');
    }

    /** @test */
    public function it_throws_exception_when_ai_service_fails(): void
    {
        $originalPrompt = 'Test prompt';
        
        // Mock du service IA qui échoue
        $mockAiService = $this->mock(AiServiceInterface::class);
        $mockAiService->shouldReceive('chat')
            ->once()
            ->andThrow(new \Exception('API Error'));
        
        $this->app->bind(AiServiceInterface::class, fn() => $mockAiService);
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Erreur lors de l\'amélioration du prompt: API Error');
        
        $this->service->enhancePrompt($this->account, $originalPrompt);
    }

    /** @test */
    public function it_prefers_account_model_over_default(): void
    {
        // Créer un second modèle Ollama par défaut
        $defaultModel = AiModel::factory()->create([
            'name' => 'Default Ollama',
            'provider' => 'ollama',
            'model_identifier' => 'llama2',
            'is_active' => true,
            'is_default' => true,
        ]);
        
        $originalPrompt = 'Test';
        $enhancedPrompt = 'Test amélioré';
        
        // Mock du service IA - doit utiliser le modèle du compte, pas le défaut
        $mockAiService = $this->mock(AiServiceInterface::class);
        $mockAiService->shouldReceive('chat')
            ->once()
            ->with(
                \Mockery::on(fn($model) => $model->id === $this->ollamaModel->id),
                \Mockery::type(AiRequestDTO::class)
            )
            ->andReturn(new AiResponseDTO(
                content: $enhancedPrompt,
                tokensUsed: 25,
            ));
        
        $this->app->bind(AiServiceInterface::class, fn() => $mockAiService);
        
        $result = $this->service->enhancePrompt($this->account, $originalPrompt);
        
        $this->assertEquals($enhancedPrompt, $result);
    }

    /** @test */
    public function it_handles_empty_ai_response(): void
    {
        $originalPrompt = 'Test prompt';
        
        // Mock du service IA qui retourne une réponse vide
        $mockAiService = $this->mock(AiServiceInterface::class);
        $mockAiService->shouldReceive('chat')
            ->once()
            ->andReturn(new AiResponseDTO(
                content: '',
                tokensUsed: 0,
            ));
        
        $this->app->bind(AiServiceInterface::class, fn() => $mockAiService);
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('L\'IA n\'a pas pu améliorer le prompt');
        
        $this->service->enhancePrompt($this->account, $originalPrompt);
    }

    /** @test */
    public function it_uses_correct_system_prompt_for_enhancement(): void
    {
        $originalPrompt = 'Tu es un bot.';
        $enhancedPrompt = 'Tu es un assistant professionnel.';
        
        // Mock du service IA pour vérifier le contenu du système prompt
        $mockAiService = $this->mock(AiServiceInterface::class);
        $mockAiService->shouldReceive('chat')
            ->once()
            ->with(
                $this->ollamaModel,
                \Mockery::on(function (AiRequestDTO $request) {
                    return str_contains($request->systemPrompt, 'expert en création de prompts') &&
                           str_contains($request->systemPrompt, 'WhatsApp') &&
                           str_contains($request->userMessage, 'Tu es un bot.');
                })
            )
            ->andReturn(new AiResponseDTO(
                content: $enhancedPrompt,
                tokensUsed: 40,
            ));
        
        $this->app->bind(AiServiceInterface::class, fn() => $mockAiService);
        
        $result = $this->service->enhancePrompt($this->account, $originalPrompt);
        
        $this->assertEquals($enhancedPrompt, $result);
    }
}
