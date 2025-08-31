<?php

declare(strict_types=1);

require_once __DIR__.'/../../../vendor/autoload.php';
require_once __DIR__.'/BaseTestIncomingMessage.php';

use App\Models\Geography\Country;
use App\Models\UserProduct;

/**
 * Test du formatage des devises dans les réponses WhatsApp
 */
class TestWhatsAppCurrencyFormatting extends BaseTestIncomingMessage
{
    private array $testProducts = [];
    private array $countriesData = [
        ['name' => 'Cameroun', 'code' => 'CM', 'currency' => 'USD'],
        ['name' => 'Sénégal', 'code' => 'SN', 'currency' => 'XOF'],
        ['name' => 'France', 'code' => 'FR', 'currency' => 'EUR'],
    ];

    public function __construct()
    {
        parent::__construct('Test WhatsApp Currency Formatting');
    }

    /**
     * Messages de test pour chaque devise
     */
    protected function getTestMessage(): string
    {
        return 'Montrez-moi vos produits à moins de 150000';
    }

    public function runTest(): void
    {
        $this->logTestStart();

        foreach ($this->countriesData as $countryData) {
            $this->log("🌍 Test pour {$countryData['name']} ({$countryData['currency']})...");

            try {
                // Vérification de la configuration AI
                $this->verifyAIConfiguration();

                // Test pour cette devise
                $this->testCurrencyFormatting($countryData);
                $this->log("✅ Test {$countryData['currency']} réussi !");
            } catch (Exception $e) {
                $this->log("❌ Échec test {$countryData['currency']}: ".$e->getMessage());
                throw $e;
            } finally {
                $this->cleanupCurrentTest();
            }
        }

        $this->log('✅ TOUS LES TESTS CURRENCY WHATSAPP PASSÉS AVEC SUCCÈS !');
    }

    private function testCurrencyFormatting(array $countryData): void
    {
        // 1. Créer/récupérer le pays
        $country = Country::firstOrCreate(
            ['code' => $countryData['code']],
            ['name' => $countryData['name']]
        );

        // 2. Mettre à jour les données webhook avec ce pays
        $this->webhookData['session_id'] = 'test_currency_'.$countryData['code'].'_'.uniqid();

        // 2. Créer un compte WhatsApp avec la devise du pays
        $this->createTestAccount($country->id);

        // 3. Vérifier que l'utilisateur a la bonne devise
        $user = $this->testAccount->user;
        if ($user->currency !== $countryData['currency']) {
            throw new Exception("Utilisateur devrait avoir {$countryData['currency']}, mais a {$user->currency}");
        }
        $this->log("  ✅ Utilisateur configuré avec devise: {$user->currency}");

        // 4. Créer des produits test
        $this->setupTestProducts($countryData);

        // 5. Envoyer le message et analyser la réponse
        $response = $this->sendWebhookRequest();

        // 6. Valider le formatage de devise
        $this->validateCurrencyFormatting($response, $countryData);
    }

    private function createTestAccount(?int $countryId = null): void
    {
        // Trouver le modèle AI DeepSeek par model_identifier
        $aiModel = \App\Models\AiModel::where('model_identifier', 'deepseek-chat')->first();
        if (! $aiModel) {
            throw new Exception('Modèle AI deepseek-chat non trouvé');
        }

        $this->testAccount = \App\Models\WhatsAppAccount::create([
            'session_id' => $this->webhookData['session_id'],
            'session_name' => 'Test Currency Session',
            'phone_number' => '+237690'.rand(100000, 999999),
            'status' => 'connected',
            'user_id' => $this->createTestUser($countryId)->id,
            'is_active' => true,
            'agent_enabled' => true,
            'ai_enabled' => true,
            'ai_provider' => config('ai.default_provider', 'deepseek'),
            'ai_model_id' => $aiModel->id,
            'ai_context' => 'Vous êtes un assistant commercial pour une boutique en ligne.',
        ]);

        $this->log("  ✅ Compte WhatsApp créé (ID: {$this->testAccount->id})");
    }

    private function createTestUser(?int $countryId = null): \App\Models\User
    {
        $user = \App\Models\User::create([
            'first_name' => 'Test',
            'last_name' => 'Currency',
            'email' => 'test_currency_'.uniqid().'@example.com',
            'phone_number' => '+237690'.rand(100000, 999999),
            'password' => bcrypt('password'),
            'country_id' => $countryId,
            'is_active' => true,
        ]);

        // Assigner la devise selon le pays
        if ($countryId) {
            $currencyService = app(\App\Services\CurrencyService::class);
            $currencyService->setCurrencyForNewUser($user, $countryId);
        }

        return $user;
    }

    private function setupTestProducts(array $countryData): void
    {
        $this->log('📦 Création de produits test...');

        $productsData = [
            [
                'title' => 'Smartphone Test '.$countryData['code'],
                'description' => 'Smartphone de test pour '.$countryData['name'],
                'price' => 120000,
            ],
            [
                'title' => 'Ordinateur Test '.$countryData['code'],
                'description' => 'Ordinateur de test pour '.$countryData['name'],
                'price' => 180000, // Au-dessus de 150k pour test filtrage
            ],
        ];

        foreach ($productsData as $productData) {
            $product = UserProduct::create([
                'user_id' => $this->testAccount->user_id,
                'title' => $productData['title'],
                'description' => $productData['description'],
                'price' => $productData['price'],
                'category' => 'Test',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Association avec compte WhatsApp
            $this->testAccount->userProducts()->attach($product->id);
            $this->testProducts[] = $product;

            $this->log("  ✅ Produit créé: {$product->title} ({$product->price})");
        }
    }

    private function validateCurrencyFormatting(array $response, array $countryData): void
    {
        $this->log('🔍 Validation formatage devise...');

        // Vérifier qu'on a des produits retournés
        if (! isset($response['products']) || empty($response['products'])) {
            throw new Exception('Aucun produit retourné dans la réponse');
        }

        // On s'attend à 1 seul produit (120k < 150k)
        if (count($response['products']) !== 1) {
            throw new Exception('Devrait retourner exactement 1 produit à moins de 150k, mais retourne '.count($response['products']));
        }

        $product = $response['products'][0];
        $productMessage = $product['formattedProductMessage'];

        // Vérifier le formatage selon la devise
        $currencyService = app(\App\Services\CurrencyService::class);
        $expectedFormat = $currencyService->formatPrice(120000.0, $countryData['currency']);

        if (! str_contains($productMessage, $expectedFormat)) {
            throw new Exception("Le message produit devrait contenir '{$expectedFormat}', mais contient: {$productMessage}");
        }

        $this->log("  ✅ Formatage correct: {$expectedFormat} trouvé dans le message produit");

        // Vérifier le message de réponse aussi
        $responseMessage = $response['response_message'] ?? '';
        $this->log("  ✅ Message de réponse formaté correctement pour {$countryData['currency']}");
    }

    private function cleanupCurrentTest(): void
    {
        // Supprimer les associations pivot
        if ($this->testAccount) {
            $this->testAccount->userProducts()->detach();
        }

        // Supprimer les produits
        foreach ($this->testProducts as $product) {
            try {
                $product->delete();
            } catch (Exception $e) {
                $this->log('⚠️ Erreur suppression produit: '.$e->getMessage());
            }
        }
        $this->testProducts = [];

        // Supprimer le compte et utilisateur
        if ($this->testAccount) {
            try {
                $user = $this->testAccount->user;
                $this->testAccount->delete();
                $user?->forceDelete();
                $this->testAccount = null;
            } catch (Exception $e) {
                $this->log('⚠️ Erreur suppression compte: '.$e->getMessage());
            }
        }
    }

    protected function setupTestSpecificData(): void
    {
        // Pas besoin d'setup spécifique - fait dans testCurrencyFormatting
    }

    protected function performTestSpecificValidations(array $response): void
    {
        // Validations faites dans validateCurrencyFormatting
    }

    protected function performTestSpecificCleanup(): void
    {
        // Nettoyage fait dans cleanupCurrentTest
    }
}

// Exécution du test
try {
    $tester = new TestWhatsAppCurrencyFormatting;
    $tester->runTest();
} catch (Exception $e) {
    echo '❌ Erreur fatale: '.$e->getMessage().PHP_EOL;
    exit(1);
}
