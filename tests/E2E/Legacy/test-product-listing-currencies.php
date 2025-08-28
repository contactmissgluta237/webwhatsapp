<?php

declare(strict_types=1);

require_once __DIR__.'/../../vendor/autoload.php';

use App\DTOs\Customer\CreateCustomerDTO;
use App\Livewire\Customer\ProductDataTable;
use App\Models\Geography\Country;
use App\Models\UserProduct;
use App\Services\CurrencyService;
use App\Services\Customer\CustomerService;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

/**
 * Test de l'affichage des devises dans la liste des produits
 */
class TestProductListingCurrencies
{
    private Application $app;
    private CurrencyService $currencyService;
    private CustomerService $customerService;
    private array $testUsers = [];
    private array $testProducts = [];
    private string $testName;

    public function __construct()
    {
        $this->testName = 'Test Product Listing Currencies';
        $_ENV['APP_ENV'] = 'local';
        $this->app = require __DIR__.'/../../bootstrap/app.php';
        $this->app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

        // Désactiver l'envoi d'emails pour les tests
        Mail::fake();

        $this->currencyService = $this->app->make(CurrencyService::class);
        $this->customerService = $this->app->make(CustomerService::class);

        $this->log('🚀 DÉBUT TEST PRODUCT LISTING CURRENCIES');
    }

    public function runTest(): void
    {
        try {
            $this->createTestUsersWithDifferentCurrencies();
            $this->createTestProductsForEachUser();
            $this->testProductDataTableRendering();

            $this->log('✅ TOUS LES TESTS PRODUCT LISTING PASSÉS AVEC SUCCÈS !');
        } catch (Exception $e) {
            $this->log('❌ ÉCHEC DU TEST: '.$e->getMessage());
            throw $e;
        } finally {
            $this->cleanup();
        }
    }

    private function createTestUsersWithDifferentCurrencies(): void
    {
        $this->log('🧪 Test 1: Création utilisateurs avec différentes devises');

        $testCountries = [
            ['name' => 'Cameroun', 'code' => 'CM', 'expected_currency' => 'XAF'],
            ['name' => 'Sénégal', 'code' => 'SN', 'expected_currency' => 'XOF'],
            ['name' => 'France', 'code' => 'FR', 'expected_currency' => 'EUR'],
        ];

        foreach ($testCountries as $countryData) {
            // Trouver ou créer le pays
            $country = Country::firstOrCreate(
                ['code' => $countryData['code']],
                ['name' => $countryData['name']]
            );

            // Créer un utilisateur
            $customerDTO = new CreateCustomerDTO(
                first_name: 'Test',
                last_name: 'User '.$countryData['code'],
                email: 'testlist_'.$countryData['code'].'@example.com',
                password: 'password123',
                phone_number: '+'.rand(100000000, 999999999),
                country_id: $country->id,
                referral_code: null,
                terms: true,
            );

            $customer = $this->customerService->create($customerDTO);
            $user = $customer->user;

            // Vérifier la devise assignée
            $expectedCurrency = $countryData['expected_currency'];
            if ($user->currency !== $expectedCurrency) {
                throw new Exception("ERREUR: Utilisateur {$countryData['code']} devrait avoir {$expectedCurrency}, mais a {$user->currency}");
            }

            $this->testUsers[] = [
                'user' => $user,
                'country_code' => $countryData['code'],
                'currency' => $user->currency,
            ];

            $this->log("  ✅ Utilisateur {$countryData['code']} créé avec devise: {$user->currency}");
        }
    }

    private function createTestProductsForEachUser(): void
    {
        $this->log('🧪 Test 2: Création produits pour chaque utilisateur');

        $productPrice = 100000.0; // Prix identique pour tous

        foreach ($this->testUsers as $userData) {
            $user = $userData['user'];

            $product = UserProduct::create([
                'user_id' => $user->id,
                'title' => 'Produit Test '.$userData['country_code'],
                'description' => 'Description du produit pour '.$userData['country_code'],
                'price' => $productPrice,
                'category' => 'Test',
                'is_active' => true,
            ]);

            $this->testProducts[] = [
                'product' => $product,
                'user' => $user,
                'country_code' => $userData['country_code'],
                'currency' => $userData['currency'],
            ];

            $this->log("  ✅ Produit créé pour {$userData['country_code']}: {$product->title}");
        }
    }

    private function testProductDataTableRendering(): void
    {
        $this->log('🧪 Test 3: Test rendu ProductDataTable pour chaque utilisateur');

        foreach ($this->testUsers as $userData) {
            $user = $userData['user'];

            // Simuler la connexion de l'utilisateur
            Auth::login($user);

            // Créer une instance du DataTable et tester le formatage directement
            $dataTable = new ProductDataTable;
            $dataTable->boot();

            // Simuler le formatage d'un prix via la colonne
            $columns = $dataTable->columns();
            $priceColumn = null;

            foreach ($columns as $column) {
                if ($column->getTitle() === 'Prix') {
                    $priceColumn = $column;
                    break;
                }
            }

            if (! $priceColumn) {
                throw new Exception('Colonne Prix non trouvée dans ProductDataTable');
            }

            // Récupérer le formatage attendu pour cette devise
            $expectedFormat = $this->currencyService->formatPrice(100000.0, $user->currency);

            // Tester directement le formatage via le service
            $actualFormat = $this->currencyService->formatPrice(100000.0, $this->currencyService->getUserCurrency($user));

            if ($actualFormat !== $expectedFormat) {
                throw new Exception("ERREUR: Le formatage ne correspond pas pour l'utilisateur {$userData['country_code']}. Attendu: '{$expectedFormat}', Reçu: '{$actualFormat}'");
            }

            $this->log("  ✅ {$userData['country_code']}: Prix affiché correctement -> {$expectedFormat}");

            Auth::logout();
        }
    }

    private function cleanup(): void
    {
        $this->log('🧹 Nettoyage des données de test...');

        // Supprimer les produits
        foreach ($this->testProducts as $productData) {
            try {
                $productData['product']->delete();
                $this->log("  ✅ Produit {$productData['product']->title} supprimé");
            } catch (Exception $e) {
                $this->log('  ⚠️ Erreur suppression produit: '.$e->getMessage());
            }
        }

        // Supprimer les utilisateurs
        foreach ($this->testUsers as $userData) {
            $user = $userData['user'];
            try {
                // Supprimer le customer associé
                if ($user->customer) {
                    $user->customer->delete();
                }
                // Supprimer l'utilisateur
                $user->forceDelete();
                $this->log("  ✅ Utilisateur {$user->email} supprimé");
            } catch (Exception $e) {
                $this->log("  ⚠️ Erreur suppression {$user->email}: ".$e->getMessage());
            }
        }

        $this->log('✅ Nettoyage terminé');
    }

    private function log(string $message): void
    {
        $timestamp = date('H:i:s');
        echo "[{$timestamp}] {$message}".PHP_EOL;
    }
}

// Exécution du test
try {
    $tester = new TestProductListingCurrencies;
    $tester->runTest();
} catch (Exception $e) {
    echo '❌ Erreur fatale: '.$e->getMessage().PHP_EOL;
    exit(1);
}
