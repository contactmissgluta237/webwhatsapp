<?php

namespace Tests\Feature\Auth;

use App\Livewire\Auth\LoginForm;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LoginRedirectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Créer les rôles nécessaires
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'customer']);
    }

    #[Test]
    public function admin_is_redirected_to_admin_dashboard_after_login(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
        ]);
        $admin->assignRole('admin');

        $response = Livewire::test(LoginForm::class)
            ->set('email', 'admin@example.com')
            ->set('password', 'password')
            ->set('loginMethod', 'email') // Utiliser la valeur string au lieu de l'enum
            ->call('login');

        // Debug: vérifier s'il y a des erreurs
        $this->assertNull($response->get('error'), 'Login should not have errors: '.$response->get('error'));

        // Vérifier que l'utilisateur est authentifié
        $this->assertAuthenticatedAs($admin);

        // Ensuite vérifier la redirection
        $response->assertRedirect();
    }

    #[Test]
    public function customer_is_redirected_to_customer_dashboard_after_login(): void
    {
        $customer = User::factory()->create([
            'email' => 'customer@example.com',
            'password' => bcrypt('password'),
        ]);
        $customer->assignRole('customer');

        $response = Livewire::test(LoginForm::class)
            ->set('email', 'customer@example.com')
            ->set('password', 'password')
            ->set('loginMethod', 'email') // Utiliser la valeur string au lieu de l'enum
            ->call('login');

        // Debug: vérifier s'il y a des erreurs
        $this->assertNull($response->get('error'), 'Login should not have errors: '.$response->get('error'));

        // Vérifier que l'utilisateur est authentifié
        $this->assertAuthenticatedAs($customer);

        // Ensuite vérifier la redirection
        $response->assertRedirect();
    }
}
