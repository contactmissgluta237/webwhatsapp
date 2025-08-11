<?php

namespace Tests\Feature\Customer;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CustomerDashboardTest extends TestCase
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
    public function customer_can_access_customer_dashboard(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $this->actingAs($customer)
            ->get(route('customer.dashboard'))
            ->assertOk()
            ->assertSee('Dashboard Client');
    }

    #[Test]
    public function admin_cannot_access_customer_dashboard(): void
    {
        $admin = User::factory()->create();
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
