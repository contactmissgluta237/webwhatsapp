<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

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
    public function admin_can_access_external_transactions_index(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.transactions.index'))
            ->assertOk()
            ->assertViewIs('admin.transactions.index');
    }

    #[Test]
    public function admin_can_access_internal_transactions(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.transactions.internal'))
            ->assertOk()
            ->assertViewIs('admin.transactions.internal');
    }

    #[Test]
    public function admin_can_access_recharge_page(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.transactions.recharge'))
            ->assertOk()
            ->assertViewIs('admin.transactions.recharge');
    }

    #[Test]
    public function admin_can_access_withdrawal_page(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.transactions.withdrawal'))
            ->assertOk()
            ->assertViewIs('admin.transactions.withdrawal');
    }

    #[Test]
    public function customer_cannot_access_admin_transactions(): void
    {
        $this->actingAs($this->customer)
            ->get(route('admin.transactions.index'))
            ->assertForbidden();

        $this->actingAs($this->customer)
            ->get(route('admin.transactions.internal'))
            ->assertForbidden();

        $this->actingAs($this->customer)
            ->get(route('admin.transactions.recharge'))
            ->assertForbidden();

        $this->actingAs($this->customer)
            ->get(route('admin.transactions.withdrawal'))
            ->assertForbidden();
    }

    #[Test]
    public function guest_cannot_access_admin_transactions(): void
    {
        $this->get(route('admin.transactions.index'))
            ->assertRedirect(route('login'));

        $this->get(route('admin.transactions.internal'))
            ->assertRedirect(route('login'));

        $this->get(route('admin.transactions.recharge'))
            ->assertRedirect(route('login'));

        $this->get(route('admin.transactions.withdrawal'))
            ->assertRedirect(route('login'));
    }
}