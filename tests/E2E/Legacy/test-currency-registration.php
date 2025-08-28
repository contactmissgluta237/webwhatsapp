<?php

declare(strict_types=1);

require_once __DIR__.'/../../vendor/autoload.php';

use App\DTOs\Customer\CreateCustomerDTO;
use App\Models\Geography\Country;
use App\Services\CurrencyService;
use App\Services\Customer\CustomerService;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Mail;

/**
 * Test complet du système de devises lors de l'inscription
 */
class TestCurrencyRegistration
{
    private Application $app;
    private CurrencyService $currencyService;
    private CustomerService $customerService;
    private array $testUsers = [];
    private string $testName;

    public function __construct()
    {
        $this->testName = 'Test Currency Registration';
        $_ENV['APP_ENV'] = 'local';
        $this->app = require __DIR__.'/../../bootstrap/app.php';
        $this->app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

        // Désactiver l'envoi d'emails pour les tests
        Mail::fake();

        $this->currencyService = $this->app->make(CurrencyService::class);
        $this->customerService = $this->app->make(CustomerService::class);

        $this->log('🚀 DÉBUT TEST CURRENCY REGISTRATION');
    }

    public function runTest(): void
    {
        try {
            $this->testCountryCurrencyMapping();
            $this->testUserRegistrationWithCountry();
            $this->testUserRegistrationWithoutCountry();
            $this->testCurrencyInProductListing();

            $this->log('✅ TOUS LES TESTS CURRENCY PASSÉS AVEC SUCCÈS !');
        } catch (Exception $e) {
            $this->log('❌ ÉCHEC DU TEST: '.$e->getMessage());
            throw $e;
        } finally {
            $this->cleanup();
        }
    }

    private function testCountryCurrencyMapping(): void
    {
        $this->log('🧪 Test 1: Mapping pays -> devise');

        // Test cas typiques africains
        $testCases = [
            'CM' => 'XAF',  // Cameroun -> BEAC
            'SN' => 'XOF',  // Sénégal -> BCEAO
            'FR' => 'EUR',  // France -> Euro
            'US' => 'USD',  // USA -> Dollar
            'XX' => 'XAF',  // Pays inexistant -> Défaut
        ];

        foreach ($testCases as $countryCode => $expectedCurrency) {
            $actualCurrency = $this->currencyService->getCurrencyByCountry($countryCode);
            if ($actualCurrency !== $expectedCurrency) {
                throw new Exception("ERREUR: {$countryCode} devrait donner {$expectedCurrency}, mais donne {$actualCurrency}");
            }
            $this->log("  ✅ {$countryCode} -> {$actualCurrency}");
        }
    }

    private function testUserRegistrationWithCountry(): void
    {
        $this->log('🧪 Test 2: Inscription utilisateur avec pays');

        // Créer des pays de test
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

            // Créer un utilisateur via CustomerService
            $customerDTO = new CreateCustomerDTO(
                first_name: 'Test',
                last_name: 'User '.$countryData['code'],
                email: 'test_'.$countryData['code'].'@example.com',
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

            $this->testUsers[] = $user;
            $this->log("  ✅ Utilisateur {$countryData['code']} -> {$user->currency}");
        }
    }

    private function testUserRegistrationWithoutCountry(): void
    {
        $this->log('🧪 Test 3: Inscription utilisateur sans pays');

        // Créer un utilisateur sans pays
        $customerDTO = new CreateCustomerDTO(
            first_name: 'Test',
            last_name: 'NoCountry',
            email: 'test_nocountry@example.com',
            password: 'password123',
            phone_number: '+'.rand(100000000, 999999999),
            country_id: null,
            referral_code: null,
            terms: true,
        );

        $customer = $this->customerService->create($customerDTO);
        $user = $customer->user;

        // Vérifier la devise par défaut
        $defaultCurrency = $this->currencyService->getDefaultCurrency();
        if ($user->currency !== $defaultCurrency) {
            throw new Exception("ERREUR: Utilisateur sans pays devrait avoir {$defaultCurrency}, mais a {$user->currency}");
        }

        $this->testUsers[] = $user;
        $this->log("  ✅ Utilisateur sans pays -> {$user->currency} (défaut)");
    }

    private function testCurrencyInProductListing(): void
    {
        $this->log('🧪 Test 4: Formatage prix selon devise utilisateur');

        $testPrice = 150000.0;

        foreach ($this->testUsers as $user) {
            $userCurrency = $this->currencyService->getUserCurrency($user);
            $formattedPrice = $this->currencyService->formatPrice($testPrice, $userCurrency);

            // Vérifier le format selon la devise (basé sur la config réelle)
            $expectedFormats = [
                'XAF' => '150 000 XAF',
                'XOF' => '150 000 F CFA',
                'EUR' => '€ 150 000,00', // EUR a 2 décimales
                'USD' => '$ 150 000,00', // USD a 2 décimales
            ];

            if (isset($expectedFormats[$userCurrency])) {
                $expectedFormat = $expectedFormats[$userCurrency];
                if ($formattedPrice !== $expectedFormat) {
                    throw new Exception("ERREUR: Prix {$testPrice} en {$userCurrency} devrait être '{$expectedFormat}', mais est '{$formattedPrice}'");
                }
                $this->log("  ✅ {$userCurrency}: {$testPrice} -> '{$formattedPrice}'");
            } else {
                $this->log("  ✅ {$userCurrency}: {$testPrice} -> '{$formattedPrice}' (format personnalisé)");
            }
        }
    }

    private function cleanup(): void
    {
        $this->log('🧹 Nettoyage des données de test...');

        foreach ($this->testUsers as $user) {
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
    $tester = new TestCurrencyRegistration;
    $tester->runTest();
} catch (Exception $e) {
    echo '❌ Erreur fatale: '.$e->getMessage().PHP_EOL;
    exit(1);
}
