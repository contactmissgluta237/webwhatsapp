<?php

namespace Tests\Feature\Auth;

use App\Enums\LoginChannel;
use App\Livewire\Auth\LoginForm;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Créer manuellement les rôles pour éviter les problèmes de seeder
        \Spatie\Permission\Models\Role::create(['name' => 'customer']);
        \Spatie\Permission\Models\Role::create(['name' => 'admin']);

        // Créer un pays avec l'ID 1 pour éviter les erreurs de validation
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
    }

    /** @test */
    public function users_can_view_login_form()
    {
        $response = $this->get(route('login'));

        $response->assertSuccessful();
        $response->assertSeeLivewire(LoginForm::class);
    }

    /** @test */
    public function users_can_login_with_valid_email_credentials()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);
        $user->assignRole('customer');

        Livewire::test(LoginForm::class)
            ->set('email', 'test@example.com')
            ->set('loginMethod', 'email')
            ->set('password', 'password123')
            ->call('login')
            ->assertRedirect();

        $this->assertAuthenticatedAs($user);
    }

    /** @test */
    public function users_can_login_with_valid_phone_credentials()
    {
        $user = User::factory()->create([
            'phone_number' => '+237655332183',
            'password' => bcrypt('password123'),
        ]);
        $user->assignRole('customer');

        Livewire::test(LoginForm::class)
            ->call('phoneUpdated', [
                'name' => 'phone_number',
                'value' => '+237655332183',
                'country_id' => 1,
                'phone_number' => '655332183',
            ])
            ->set('loginMethod', LoginChannel::PHONE())
            ->set('password', 'password123')
            ->call('login')
            ->assertRedirect();

        $this->assertAuthenticatedAs($user);
    }

    /** @test */
    public function users_cannot_login_with_invalid_credentials()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        Livewire::test(LoginForm::class)
            ->set('email', 'test@example.com')
            ->set('loginMethod', 'email')
            ->set('password', 'wrong-password')
            ->call('login')
            ->assertSet('error', 'Identifiants incorrects. Veuillez réessayer.');

        $this->assertGuest();
    }

    /** @test */
    public function authenticated_users_cannot_view_login_form()
    {
        $user = User::factory()->create();
        $user->assignRole('customer');
        $this->actingAs($user);

        $response = $this->get(route('login'));

        $response->assertRedirect('/');
    }
}
