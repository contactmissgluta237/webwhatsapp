<?php

declare(strict_types=1);

namespace Tests\Feature\WhatsApp;

use App\Models\AiModel;
use App\Models\User;
use App\Models\WhatsAppAccount;
use App\Services\AI\PromptEnhancementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Helpers\AiTestHelper;
use Tests\TestCase;

final class PromptEnhancementIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private WhatsAppAccount $account;
    private AiModel $ollamaModel;
    private PromptEnhancementService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        // Créer un modèle Ollama avec le bon endpoint basé sur la configuration centralisée
        $this->ollamaModel = AiModel::factory()->create(
            AiTestHelper::createTestModelData('ollama', [
                'name' => 'GenericIA (Ollama Gemma2)',
                'is_default' => true,
            ])
        );

        $this->account = WhatsAppAccount::factory()->create([
            'user_id' => $this->user->id,
            'ai_model_id' => $this->ollamaModel->id,
        ]);

        $this->service = app(PromptEnhancementService::class);
    }

    public function test_can_enhance_prompt_with_mocked_ollama_response(): void
    {
        $originalPrompt = 'Aide les clients de notre boutique';
        $enhancedPrompt = 'Tu es un assistant professionnel pour une boutique. Tu aides les clients avec courtoisie et professionnalisme. Réponds en français de manière claire et utile.';

        // Mock de la réponse HTTP d'Ollama
        Http::fake([
            'http://209.126.83.125:11434/api/chat' => Http::response([
                'model' => 'gemma2:2b',
                'created_at' => '2023-08-04T08:52:19.385406455Z',
                'message' => [
                    'role' => 'assistant',
                    'content' => $enhancedPrompt,
                ],
                'done' => true,
                'total_duration' => 5191566416,
                'load_duration' => 2154458,
                'prompt_eval_count' => 26,
                'prompt_eval_duration' => 383809000,
                'eval_count' => 298,
                'eval_duration' => 4799921000,
            ], 200),
        ]);

        $result = $this->service->enhancePrompt($this->account, $originalPrompt);

        $this->assertEquals($enhancedPrompt, $result);

        // Vérifier que l'API a été appelée avec les bons paramètres
        Http::assertSent(function ($request) use ($originalPrompt) {
            $body = $request->data();

            return $request->url() === 'http://209.126.83.125:11434/api/chat'
                && $body['model'] === 'gemma2:2b'
                && str_contains($body['messages'][1]['content'], $originalPrompt)
                && str_contains($body['messages'][0]['content'], 'WhatsApp')
                && $body['options']['temperature'] === 0.3
                && $body['stream'] === false;
        });
    }

    public function test_handles_ollama_api_error_gracefully(): void
    {
        $originalPrompt = 'Test prompt';

        // Mock d'une erreur API
        Http::fake([
            'http://209.126.83.125:11434/api/chat' => Http::response([
                'error' => 'Model not found',
            ], 404),
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Impossible d\'améliorer le prompt');

        $this->service->enhancePrompt($this->account, $originalPrompt);
    }

    public function test_enhancement_service_uses_correct_model_priority(): void
    {
        // Désactiver tous les modèles existants du seeder
        \App\Models\AiModel::query()->update(['is_active' => false, 'is_default' => false]);

        // Créer plusieurs modèles pour tester la priorité
        $specificModel = AiModel::factory()->create([
            'name' => 'Specific Model',
            'provider' => 'ollama',
            'endpoint_url' => 'http://specific-endpoint:11434',
            'is_active' => true,
            'is_default' => false,
        ]);

        $defaultModel = AiModel::factory()->create(
            AiTestHelper::createTestModelData('ollama', [
                'name' => 'Default Model',
                'endpoint_url' => 'http://default-endpoint:11434',
                'is_default' => true,
            ])
        );        // Mock toutes les réponses possibles
        Http::fake([
            'http://specific-endpoint:11434/api/chat' => Http::response([
                'message' => ['role' => 'assistant', 'content' => 'Enhanced by specific model'],
                'done' => true,
            ], 200),
            'http://default-endpoint:11434/api/chat' => Http::response([
                'message' => ['role' => 'assistant', 'content' => 'Enhanced by default model'],
                'done' => true,
            ], 200),
            // Bloquer tous les autres endpoints
            '*' => Http::response(['error' => 'Unauthorized endpoint'], 404),
        ]);

        // Test 1: Utilise le modèle configuré sur le compte
        $this->account->update(['ai_model_id' => $specificModel->id]);
        $this->account->refresh();

        $result = $this->service->enhancePrompt($this->account, 'Test specific');
        $this->assertEquals('Enhanced by specific model', $result);

        // Test 2: Utilise le modèle par défaut si aucun configuré
        $this->account->update(['ai_model_id' => null]);
        $this->account->refresh();

        $result = $this->service->enhancePrompt($this->account, 'Test default');
        $this->assertEquals('Enhanced by default model', $result);
    }

    public function test_enhancement_service_timeout_handling(): void
    {
        $originalPrompt = 'Test prompt';

        // Mock d'un timeout
        Http::fake([
            'http://209.126.83.125:11434/api/chat' => function () {
                throw new \Illuminate\Http\Client\ConnectionException('Connection timeout');
            },
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Impossible d\'améliorer le prompt');

        $this->service->enhancePrompt($this->account, $originalPrompt);
    }

    public function test_enhancement_preserves_original_intent(): void
    {
        $originalPrompt = 'Tu es un vendeur de voitures. Sois sympa.';
        $enhancedPrompt = 'Tu es un assistant commercial professionnel spécialisé dans la vente automobile. Tu accueilles les clients avec chaleur et professionnalisme, tu les aides à trouver le véhicule qui correspond à leurs besoins en posant les bonnes questions. Réponds toujours en français de manière courtoise et utile.';

        Http::fake([
            'http://209.126.83.125:11434/api/chat' => Http::response([
                'message' => [
                    'role' => 'assistant',
                    'content' => $enhancedPrompt,
                ],
                'done' => true,
            ], 200),
        ]);

        $result = $this->service->enhancePrompt($this->account, $originalPrompt);

        // Vérifier que le résultat est plus long que l'original
        $this->assertGreaterThan(strlen($originalPrompt), strlen($result));

        // Vérifier que l'API a été appelée avec le prompt original
        Http::assertSent(function ($request) use ($originalPrompt) {
            $body = $request->data();

            return str_contains($body['messages'][1]['content'], $originalPrompt);
        });

        // Vérifier que le résultat correspond au mock
        $this->assertEquals($enhancedPrompt, $result);

        // Vérifier que le service utilise les bons paramètres
        Http::assertSent(function ($request) {
            $body = $request->data();

            return $body['options']['temperature'] === 0.3
                && $body['model'] === 'gemma2:2b'
                && $body['stream'] === false;
        });
    }
}
