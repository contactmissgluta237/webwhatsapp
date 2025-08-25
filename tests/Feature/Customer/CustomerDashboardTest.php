<?php

namespace Tests\Feature\Customer;

use App\Enums\PermissionEnum;
use App\Enums\UserRole;
use App\Models\Geography\Country;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CustomerDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Créer un pays par défaut pour les utilisateurs
        Country::create([
            'id' => 1,
            'name' => 'Cameroun',
            'code' => 'CM',
            'phone_code' => '+237',
            'flag_emoji' => '🇨🇲',
            'is_active' => true,
        ]);

        // Créer les permissions nécessaires
        $permissions = PermissionEnum::values();
        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Créer les rôles nécessaires avec leurs permissions
        $adminRole = Role::create(['name' => 'admin']);
        $customerRole = Role::create(['name' => 'customer']);

        // Assigner les permissions selon UserRole
        $customerRole->givePermissionTo(UserRole::CUSTOMER()->permissions());
        $adminRole->givePermissionTo(PermissionEnum::values()); // Admin a toutes les permissions
    }

    #[Test]
    public function customer_can_access_customer_dashboard(): void
    {
        $customer = User::factory()->create([
            'country_id' => 1,
            'currency' => 'XAF',
        ]);
        $customer->assignRole('customer');

        // Créer un wallet pour le customer
        Wallet::create([
            'user_id' => $customer->id,
            'balance' => 1000.00,
        ]);

        $this->actingAs($customer)
            ->get(route('customer.dashboard'))
            ->assertOk();
    }

    #[Test]
    public function admin_cannot_access_customer_dashboard(): void
    {
        $admin = User::factory()->create([
            'country_id' => 1,
            'currency' => 'XAF',
        ]);
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->get(route('customer.dashboard'))
            ->assertForbidden();
    }

    #[Test]
    public function guest_cannot_access_customer_dashboard(): void
    {
        $this->get(route('customer.dashboard'))
            ->assertRedirect('/login');
    }
}
