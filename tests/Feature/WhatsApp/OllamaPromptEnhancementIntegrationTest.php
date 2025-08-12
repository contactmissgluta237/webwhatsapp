<?php

declare(strict_types=1);

namespace Tests\Feature\WhatsApp;

use App\Models\AiModel;
use App\Models\User;
use App\Models\WhatsAppAccount;
use App\Services\AI\PromptEnhancementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test d'intégration avec le vrai service Ollama
 * Ces tests nécessitent qu'Ollama soit accessible
 * 
 * @group integration
 * @group ollama
 */
final class OllamaPromptEnhancementIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private PromptEnhancementService $service;
    private WhatsAppAccount $account;
    private AiModel $ollamaModel;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = app(PromptEnhancementService::class);
        
        // Créer un modèle Ollama basé sur les données du seeder
        $this->ollamaModel = AiModel::factory()->create([
            'name' => 'GenericIA (Ollama Gemma2)',
            'provider' => 'ollama',
            'model_identifier' => 'gemma2:2b',
            'endpoint_url' => 'http://209.126.83.125:11434',
            'requires_api_key' => false,
            'is_active' => true,
            'is_default' => true,
            'model_config' => [
                'temperature' => 0.7,
                'max_tokens' => 1000,
                'top_p' => 0.9,
            ],
        ]);
        
        $user = User::factory()->create();
        $this->account = WhatsAppAccount::factory()->create([
            'user_id' => $user->id,
            'ai_model_id' => $this->ollamaModel->id,
            'agent_enabled' => true,
        ]);
    }

    /** @test */
    public function it_can_enhance_basic_prompt_with_real_ollama(): void
    {
        $this->markTestSkipped('Test d\'intégration avec Ollama - à activer manuellement');
        
        $originalPrompt = 'Tu es un assistant.';
        
        try {
            $enhancedPrompt = $this->service->enhancePrompt($this->account, $originalPrompt);
            
            $this->assertNotEmpty($enhancedPrompt);
            $this->assertNotEquals($originalPrompt, $enhancedPrompt);
            $this->assertGreaterThan(strlen($originalPrompt), strlen($enhancedPrompt));
            
            // Vérifier que le prompt amélioré contient des mots-clés attendus
            $this->assertStringContainsStringIgnoringCase('whatsapp', $enhancedPrompt);
            
            echo "\n--- Prompt original ---\n";
            echo $originalPrompt;
            echo "\n--- Prompt amélioré ---\n";
            echo $enhancedPrompt;
            echo "\n";
            
        } catch (\Exception $e) {
            $this->markTestSkipped("Ollama non accessible: " . $e->getMessage());
        }
    }

    /** @test */
    public function it_can_enhance_customer_service_prompt_with_real_ollama(): void
    {
        $this->markTestSkipped('Test d\'intégration avec Ollama - à activer manuellement');
        
        $originalPrompt = 'Je réponds aux questions des clients sur les produits.';
        
        try {
            $enhancedPrompt = $this->service->enhancePrompt($this->account, $originalPrompt);
            
            $this->assertNotEmpty($enhancedPrompt);
            $this->assertNotEquals($originalPrompt, $enhancedPrompt);
            
            // Vérifier que le prompt amélioré est plus structuré
            $this->assertStringContainsStringIgnoringCase('client', $enhancedPrompt);
            $this->assertStringContainsStringIgnoringCase('professionnel', $enhancedPrompt);
            
            echo "\n--- Prompt support client original ---\n";
            echo $originalPrompt;
            echo "\n--- Prompt support client amélioré ---\n";
            echo $enhancedPrompt;
            echo "\n";
            
        } catch (\Exception $e) {
            $this->markTestSkipped("Ollama non accessible: " . $e->getMessage());
        }
    }

    /** @test */
    public function it_can_enhance_restaurant_prompt_with_real_ollama(): void
    {
        $this->markTestSkipped('Test d\'intégration avec Ollama - à activer manuellement');
        
        $originalPrompt = 'Je prends les commandes pour un restaurant.';
        
        try {
            $enhancedPrompt = $this->service->enhancePrompt($this->account, $originalPrompt);
            
            $this->assertNotEmpty($enhancedPrompt);
            $this->assertNotEquals($originalPrompt, $enhancedPrompt);
            
            // Vérifier que le prompt amélioré est adapté à la restauration
            $this->assertStringContainsStringIgnoringCase('restaurant', $enhancedPrompt);
            $this->assertStringContainsStringIgnoringCase('commande', $enhancedPrompt);
            
            echo "\n--- Prompt restaurant original ---\n";
            echo $originalPrompt;
            echo "\n--- Prompt restaurant amélioré ---\n";
            echo $enhancedPrompt;
            echo "\n";
            
        } catch (\Exception $e) {
            $this->markTestSkipped("Ollama non accessible: " . $e->getMessage());
        }
    }

    /** @test */
    public function it_handles_ollama_connection_error_gracefully(): void
    {
        // Créer un modèle avec un endpoint incorrect
        $brokenModel = AiModel::factory()->create([
            'name' => 'Broken Ollama',
            'provider' => 'ollama',
            'model_identifier' => 'gemma2:2b',
            'endpoint_url' => 'http://localhost:99999', // Port inaccessible
            'requires_api_key' => false,
            'is_active' => true,
        ]);
        
        $this->account->update(['ai_model_id' => $brokenModel->id]);
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/Erreur lors de l\'amélioration du prompt/');
        
        $this->service->enhancePrompt($this->account, 'Test prompt');
    }

    /** @test */
    public function it_validates_enhanced_prompt_quality(): void
    {
        $this->markTestSkipped('Test d\'intégration avec Ollama - à activer manuellement');
        
        $originalPrompt = 'Réponds aux clients.';
        
        try {
            $enhancedPrompt = $this->service->enhancePrompt($this->account, $originalPrompt);
            
            // Validation de la qualité du prompt amélioré
            $this->assertLessThanOrEqual(500, str_word_count($enhancedPrompt), 'Le prompt ne doit pas dépasser 500 mots');
            $this->assertGreaterThan(10, str_word_count($enhancedPrompt), 'Le prompt doit contenir au moins 10 mots');
            
            // Vérifier qu'il n'y a pas de contenu inappropriate
            $inappropriate = ['xxx', 'sexe', 'violence', 'drogue'];
            foreach ($inappropriate as $word) {
                $this->assertStringNotContainsStringIgnoringCase($word, $enhancedPrompt);
            }
            
            // Vérifier qu'il contient des éléments professionnels
            $professional = ['professionnel', 'assistance', 'service', 'client', 'aide'];
            $containsProfessional = false;
            foreach ($professional as $word) {
                if (stripos($enhancedPrompt, $word) !== false) {
                    $containsProfessional = true;
                    break;
                }
            }
            $this->assertTrue($containsProfessional, 'Le prompt amélioré doit contenir des termes professionnels');
            
        } catch (\Exception $e) {
            $this->markTestSkipped("Ollama non accessible: " . $e->getMessage());
        }
    }

    /**
     * Test manuel pour vérifier la configuration Ollama
     * Lancez ce test individuellement avec: php artisan test --filter=test_ollama_configuration
     */
    public function test_ollama_configuration(): void
    {
        $this->markTestSkipped('Test de configuration - décommentez pour vérifier manuellement');
        
        // Décommentez les lignes ci-dessous pour tester manuellement
        /*
        try {
            $response = \Illuminate\Support\Facades\Http::timeout(5)
                ->get($this->ollamaModel->endpoint_url . '/api/tags');
                
            $this->assertTrue($response->successful(), 'Ollama doit être accessible');
            
            $models = $response->json('models', []);
            $this->assertNotEmpty($models, 'Ollama doit avoir des modèles installés');
            
            $hasGemma = collect($models)->contains(fn($m) => str_contains($m['name'], 'gemma'));
            $this->assertTrue($hasGemma, 'Le modèle Gemma2 doit être installé dans Ollama');
            
            echo "\n--- Modèles Ollama disponibles ---\n";
            foreach ($models as $model) {
                echo "- " . $model['name'] . "\n";
            }
            
        } catch (\Exception $e) {
            $this->fail("Erreur de connexion à Ollama: " . $e->getMessage());
        }
        */
    }
}
