<?php

namespace Tests\Unit\Livewire\Auth;

use App\Enums\UserRole;
use App\Livewire\Auth\RegisterForm;
use App\Models\User;
use App\Services\Auth\Contracts\AccountActivationServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RegisterFormTest extends TestCase
{
    use RefreshDatabase;

    private AccountActivationServiceInterface $activationService;

    protected function setUp(): void
    {
        parent::setUp();

        // Créer manuellement le rôle customer pour éviter les problèmes de seeder
        \Spatie\Permission\Models\Role::create(['name' => 'customer']);

        // Créer un pays avec l'ID 1 pour éviter les erreurs de validation
        // Utiliser DB::table car le modèle Country n'existe pas encore
        \Illuminate\Support\Facades\DB::table('countries')->insert([
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

        $this->activationService = $this->createMock(AccountActivationServiceInterface::class);
        $this->app->instance(AccountActivationServiceInterface::class, $this->activationService);
    }

    /** @test */
    public function it_renders_successfully()
    {
        Livewire::test(RegisterForm::class)
            ->assertSuccessful();
    }

    /** @test */
    public function it_validates_required_fields()
    {
        Livewire::test(RegisterForm::class)
            ->call('register')
            ->assertHasErrors([
                'first_name' => 'required',
                'last_name' => 'required',
                'email' => 'required',
                'password' => 'required',
                'terms' => 'accepted',
            ]);
    }

    /** @test */
    public function it_validates_email_format()
    {
        Livewire::test(RegisterForm::class)
            ->set('email', 'invalid-email')
            ->call('register')
            ->assertHasErrors(['email' => 'email']);
    }

    /** @test */
    public function it_validates_password_confirmation()
    {
        Livewire::test(RegisterForm::class)
            ->set('password', 'password123')
            ->set('password_confirmation', 'different')
            ->call('register')
            ->assertHasErrors(['password' => 'confirmed']);
    }

    /** @test */
    public function it_handles_phone_updated_event()
    {
        $phoneData = [
            'name' => 'phone_number',
            'value' => '+237655332183',
            'country_id' => 1,
            'phone_number' => '655332183',
        ];

        $component = Livewire::test(RegisterForm::class)
            ->call('phoneUpdated', $phoneData);

        $this->assertEquals(1, $component->get('country_id'));
        $this->assertEquals('655332183', $component->get('phone_number_only'));
        $this->assertEquals('+237655332183', $component->get('phone_number'));
    }

    /** @test */
    public function it_creates_user_successfully_with_valid_data()
    {
        $this->activationService->expects($this->once())
            ->method('sendActivationCode')
            ->with('john.doe@example.com');

        Livewire::test(RegisterForm::class)
            ->set('first_name', 'John')
            ->set('last_name', 'Doe')
            ->set('email', 'john.doe@example.com')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->set('terms', true)
            ->call('register')
            ->assertRedirect(route('account.activate', ['identifier' => 'john.doe@example.com']));

        $this->assertDatabaseHas('users', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
        ]);

        $user = User::where('email', 'john.doe@example.com')->first();
        $this->assertTrue($user->hasRole(UserRole::CUSTOMER()->value));
    }

    /** @test */
    public function it_creates_user_with_phone_number()
    {
        $this->activationService->expects($this->once())
            ->method('sendActivationCode');

        $phoneData = [
            'name' => 'phone_number',
            'value' => '+237655332183',
            'country_id' => 1,
            'phone_number' => '655332183',
        ];

        Livewire::test(RegisterForm::class)
            ->call('phoneUpdated', $phoneData)
            ->set('first_name', 'John')
            ->set('last_name', 'Doe')
            ->set('email', 'john.doe@example.com')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->set('terms', true)
            ->call('register')
            ->assertRedirect();

        $this->assertDatabaseHas('users', [
            'email' => 'john.doe@example.com',
            'phone_number' => '+237655332183',
        ]);
    }

    /** @test */
    public function it_handles_registration_exception()
    {
        $this->activationService->expects($this->once())
            ->method('sendActivationCode')
            ->willThrowException(new \Exception('Service unavailable'));

        Livewire::test(RegisterForm::class)
            ->set('first_name', 'John')
            ->set('last_name', 'Doe')
            ->set('email', 'john.doe@example.com')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->set('terms', true)
            ->call('register')
            ->assertSet('error', 'An error occurred while creating the account. Please try again.')
            ->assertSet('loading', false)
            ->assertSet('password', '')
            ->assertSet('password_confirmation', '');

        $this->assertDatabaseMissing('users', ['email' => 'john.doe@example.com']);
    }

    /** @test */
    public function it_sets_loading_state_during_registration()
    {
        $this->activationService->expects($this->once())
            ->method('sendActivationCode');

        $component = Livewire::test(RegisterForm::class)
            ->set('first_name', 'John')
            ->set('last_name', 'Doe')
            ->set('email', 'john.doe@example.com')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->set('terms', true)
            ->call('register');

        // Vérifier que loading passe à false après l'opération (dans finally)
        $component->assertSet('loading', false);
    }

    /** @test */
    public function it_clears_passwords_on_error()
    {
        $this->activationService->expects($this->once())
            ->method('sendActivationCode')
            ->willThrowException(new \Exception('Service error'));

        Livewire::test(RegisterForm::class)
            ->set('first_name', 'John')
            ->set('last_name', 'Doe')
            ->set('email', 'john.doe@example.com')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->set('terms', true)
            ->call('register')
            ->assertSet('password', '')
            ->assertSet('password_confirmation', '');
    }
}
