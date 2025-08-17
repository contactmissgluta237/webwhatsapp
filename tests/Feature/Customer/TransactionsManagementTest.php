<?php

declare(strict_types=1);

namespace Tests\Feature\Customer;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class TransactionsManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        // Créer les rôles nécessaires
        \Spatie\Permission\Models\Role::create(['name' => 'admin']);
        \Spatie\Permission\Models\Role::create(['name' => 'customer']);

        // Créer un pays pour éviter les erreurs de validation
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

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
        $this->admin = $this->admin->fresh();

        $this->customer = User::factory()->create();
        $this->customer->assignRole('customer');
        $this->customer = $this->customer->fresh();
    }

    #[Test]
    public function customer_can_access_recharge_page(): void
    {
        $this->actingAs($this->customer)
            ->get(route('customer.transactions.recharge'))
            ->assertOk()
            ->assertViewIs('customer.transactions.recharge');
    }

    #[Test]
    public function customer_can_access_withdrawal_page(): void
    {
        $this->actingAs($this->customer)
            ->get(route('customer.transactions.withdrawal'))
            ->assertOk()
            ->assertViewIs('customer.transactions.withdrawal');
    }

    #[Test]
    public function customer_can_access_internal_transactions(): void
    {
        $this->actingAs($this->customer)
            ->get(route('customer.transactions.internal'))
            ->assertOk()
            ->assertViewIs('customer.transactions.internal');
    }

    #[Test]
    public function admin_cannot_access_customer_transactions_without_customer_role(): void
    {
        $this->actingAs($this->admin)
            ->get(route('customer.transactions.recharge'))
            ->assertForbidden();

        $this->actingAs($this->admin)
            ->get(route('customer.transactions.withdrawal'))
            ->assertForbidden();

        $this->actingAs($this->admin)
            ->get(route('customer.transactions.internal'))
            ->assertForbidden();
    }

    #[Test]
    public function guest_cannot_access_customer_transactions(): void
    {
        $this->get(route('customer.transactions.recharge'))
            ->assertRedirect(route('login'));

        $this->get(route('customer.transactions.withdrawal'))
            ->assertRedirect(route('login'));

        $this->get(route('customer.transactions.internal'))
            ->assertRedirect(route('login'));
    }
}