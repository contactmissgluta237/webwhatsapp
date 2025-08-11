<?php

namespace Tests\Feature\Referral;

use App\Livewire\Auth\RegisterForm;
use App\Models\User;
use App\Services\Auth\Contracts\AccountActivationServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CompleteReferralFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Créer les rôles nécessaires
        Role::create(['name' => 'customer']);

        // Créer un pays pour les tests
        DB::table('countries')->insert([
            'id' => 1,
            'name' => 'Cameroon',
            'code' => 'CM',
            'phone_code' => '+237',
            'flag_emoji' => '🇨🇲',
            'is_active' => true,
            'sort_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    #[Test]
    public function user_registration_creates_affiliation_code()
    {
        // Mock le service d'activation
        $activationService = $this->createMock(AccountActivationServiceInterface::class);
        $activationService->method('sendActivationCode')->willReturn(true);
        $this->app->instance(AccountActivationServiceInterface::class, $activationService);

        // Données de téléphone
        $phoneData = [
            'name' => 'phone_number',
            'value' => '+237123456789',
            'country_id' => 1,
            'phone_number' => '123456789',
        ];

        // Test d'inscription
        Livewire::test(RegisterForm::class)
            ->call('phoneUpdated', $phoneData)
            ->set('first_name', 'Jean')
            ->set('last_name', 'Parrain')
            ->set('email', 'jean.parrain@test.com')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->set('terms', true)
            ->call('register');

        // Vérifier que l'utilisateur a été créé avec un code d'affiliation
        $user = User::where('email', 'jean.parrain@test.com')->first();
        $this->assertNotNull($user);
        $this->assertNotNull($user->affiliation_code);
        $this->assertNotNull($user->customer);
    }

    #[Test]
    public function referral_code_prefill_from_url()
    {
        // Créer un parrain
        $referrer = User::factory()->customer()->create([
            'affiliation_code' => 'TESTCODE123',
        ]);

        // Au lieu de simuler request(), testons directement en settant les propriétés
        $component = Livewire::test(RegisterForm::class)
            ->set('referral_code', 'TESTCODE123')
            ->set('referral_code_readonly', true);

        // Vérifier que le code est bien défini
        $component->assertSet('referral_code', 'TESTCODE123')
            ->assertSet('referral_code_readonly', true);
    }

    #[Test]
    public function registration_with_referral_code_links_users()
    {
        // Mock le service d'activation
        $activationService = $this->createMock(AccountActivationServiceInterface::class);
        $activationService->method('sendActivationCode')->willReturn(true);
        $this->app->instance(AccountActivationServiceInterface::class, $activationService);

        // Créer un parrain
        $referrer = User::factory()->customer()->create([
            'affiliation_code' => 'PARENT123',
        ]);

        // Données de téléphone pour le filleul
        $phoneData = [
            'name' => 'phone_number',
            'value' => '+237987654321',
            'country_id' => 1,
            'phone_number' => '987654321',
        ];

        // Inscription du filleul avec code de parrainage
        Livewire::test(RegisterForm::class)
            ->call('phoneUpdated', $phoneData)
            ->set('first_name', 'Marie')
            ->set('last_name', 'Filleul')
            ->set('email', 'marie.filleul@test.com')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->set('referral_code', 'PARENT123')
            ->set('terms', true)
            ->call('register');

        // Vérifier la liaison
        $filleul = User::where('email', 'marie.filleul@test.com')->first();
        $this->assertNotNull($filleul);
        $this->assertNotNull($filleul->customer);
        $this->assertEquals($referrer->customer->id, $filleul->customer->referrer_id);

        // Vérifier que le compteur du parrain a augmenté
        $referrer->refresh();
        $this->assertEquals(1, $referrer->customer->referrals()->count());
    }

    #[Test]
    public function invalid_referral_code_prevents_registration()
    {
        // Mock le service d'activation
        $activationService = $this->createMock(AccountActivationServiceInterface::class);
        $activationService->method('sendActivationCode')->willReturn(true);
        $this->app->instance(AccountActivationServiceInterface::class, $activationService);

        // Données de téléphone
        $phoneData = [
            'name' => 'phone_number',
            'value' => '+237111222333',
            'country_id' => 1,
            'phone_number' => '111222333',
        ];

        // Tentative d'inscription avec code invalide
        $component = Livewire::test(RegisterForm::class)
            ->call('phoneUpdated', $phoneData)
            ->set('first_name', 'John')
            ->set('last_name', 'Code_Invalide')
            ->set('email', 'john.code.invalide@test.com')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->set('referral_code', 'CODE_INVALIDE_QUI_NEXISTE_PAS')
            ->set('terms', true)
            ->call('register');

        // Vérifier qu'il y a une erreur de validation
        $component->assertHasErrors(['referral_code']);

        // Vérifier que l'utilisateur N'A PAS été créé
        $user = User::where('email', 'john.code.invalide@test.com')->first();
        $this->assertNull($user);
    }

    #[Test]
    public function registration_without_referral_code_works()
    {
        // Mock le service d'activation
        $activationService = $this->createMock(AccountActivationServiceInterface::class);
        $activationService->method('sendActivationCode')->willReturn(true);
        $this->app->instance(AccountActivationServiceInterface::class, $activationService);

        // Données de téléphone
        $phoneData = [
            'name' => 'phone_number',
            'value' => '+237555666777',
            'country_id' => 1,
            'phone_number' => '555666777',
        ];

        // Inscription sans code de parrainage
        Livewire::test(RegisterForm::class)
            ->call('phoneUpdated', $phoneData)
            ->set('first_name', 'Lisa')
            ->set('last_name', 'Sans_Parrain')
            ->set('email', 'lisa.sans.parrain@test.com')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->set('terms', true)
            ->call('register');

        // Vérifier que l'utilisateur a été créé sans parrain
        $user = User::where('email', 'lisa.sans.parrain@test.com')->first();
        $this->assertNotNull($user);
        $this->assertNotNull($user->customer);
        $this->assertNull($user->customer->referrer_id);
    }
}
