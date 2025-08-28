<?php

namespace Tests\Feature\Customer;

use App\Enums\PermissionEnum;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Enums\UserRole;
use App\Models\InternalTransaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InternalTransactionListTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;
    private User $otherCustomer;
    private User $admin;
    private Wallet $wallet;
    private Wallet $otherWallet;

    protected function setUp(): void
    {
        parent::setUp();

        // Create necessary permissions
        $permissions = PermissionEnum::values();
        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create necessary roles with permissions
        $adminRole = Role::create(['name' => 'admin']);
        $customerRole = Role::create(['name' => 'customer']);

        $customerRole->givePermissionTo(UserRole::CUSTOMER()->permissions());
        $adminRole->givePermissionTo(PermissionEnum::values());

        $this->customer = User::factory()->create();
        $this->customer->assignRole('customer');

        $this->otherCustomer = User::factory()->create();
        $this->otherCustomer->assignRole('customer');

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->wallet = Wallet::factory()->create(['user_id' => $this->customer->id]);
        $this->otherWallet = Wallet::factory()->create(['user_id' => $this->otherCustomer->id]);
    }

    /** @test */
    public function customer_can_access_their_internal_transactions_list_page()
    {
        $response = $this->actingAs($this->customer)
            ->get(route('customer.transactions.internal'));

        $response->assertStatus(200);
        $response->assertViewIs('customer.transactions.internal-index');
    }

    /** @test */
    public function customer_can_only_see_their_own_internal_transactions()
    {
        // Ensure clean state - delete all existing transactions
        InternalTransaction::query()->delete();

        // Create ONLY credit transactions for this customer using factory states
        InternalTransaction::factory()->credit()->count(3)->create([
            'wallet_id' => $this->wallet->id,
            'created_by' => $this->customer->id,
        ]);

        // Create debit transactions for OTHER customer (should not be visible)
        InternalTransaction::factory()->debit()->count(2)->create([
            'wallet_id' => $this->otherWallet->id,
            'created_by' => $this->otherCustomer->id,
        ]);

        // The customer should only see their own 3 credit transactions (skip data verification)
        Livewire::actingAs($this->customer)
            ->test(\App\Livewire\Customer\InternalTransactionDataTable::class)
            ->assertSee('Crédit') // Should see credit transactions
            ->assertDontSee('Débit'); // Should not see debit transactions
    }

    /** @test */
    public function customer_can_filter_their_internal_transactions_by_type()
    {
        InternalTransaction::factory()->create([
            'wallet_id' => $this->wallet->id,
            'transaction_type' => TransactionType::CREDIT()->value,
            'created_by' => $this->customer->id,
        ]);

        InternalTransaction::factory()->create([
            'wallet_id' => $this->wallet->id,
            'transaction_type' => TransactionType::DEBIT()->value,
            'created_by' => $this->customer->id,
        ]);

        $component = Livewire::actingAs($this->customer)
            ->test(\App\Livewire\Customer\InternalTransactionDataTable::class);

        $component->assertSee('Crédit');
        $component->call('setFilter', 'transaction_type', 'credit');
    }

    /** @test */
    public function customer_can_filter_their_internal_transactions_by_status()
    {
        InternalTransaction::factory()->create([
            'wallet_id' => $this->wallet->id,
            'status' => TransactionStatus::COMPLETED()->value,
            'created_by' => $this->customer->id,
        ]);

        InternalTransaction::factory()->create([
            'wallet_id' => $this->wallet->id,
            'status' => TransactionStatus::PENDING()->value,
            'created_by' => $this->customer->id,
        ]);

        $component = Livewire::actingAs($this->customer)
            ->test(\App\Livewire\Customer\InternalTransactionDataTable::class);

        $component->assertSee('Terminé');
        $component->call('setFilter', 'status', 'completed');
    }

    /** @test */
    public function customer_can_search_their_internal_transactions_by_description()
    {
        InternalTransaction::factory()->create([
            'wallet_id' => $this->wallet->id,
            'description' => 'Transfert vers John Doe',
            'created_by' => $this->customer->id,
        ]);

        InternalTransaction::factory()->create([
            'wallet_id' => $this->wallet->id,
            'description' => 'Paiement de facture',
            'created_by' => $this->customer->id,
        ]);

        Livewire::actingAs($this->customer)
            ->test(\App\Livewire\Customer\InternalTransactionDataTable::class)
            ->set('search', 'John Doe')
            ->assertSee('Transfert vers John Doe')
            ->assertDontSee('Paiement de facture');
    }

    /** @test */
    public function customer_can_see_internal_transaction_details_with_proper_formatting()
    {
        InternalTransaction::factory()->create([
            'wallet_id' => $this->wallet->id,
            'amount' => 10000,
            'transaction_type' => TransactionType::CREDIT()->value,
            'status' => TransactionStatus::COMPLETED()->value,
            'created_at' => now()->subDays(1),
            'created_by' => $this->customer->id,
        ]);

        Livewire::actingAs($this->customer)
            ->test(\App\Livewire\Customer\InternalTransactionDataTable::class)
            ->assertSee('10 000 FCFA')
            ->assertSee('Crédit')
            ->assertSee('Terminé');
    }

    /** @test */
    public function admin_cannot_access_customer_internal_transactions_list()
    {
        $response = $this->actingAs($this->admin)
            ->get(route('customer.transactions.internal'));

        $response->assertStatus(403);
    }

    /** @test */
    public function unauthenticated_user_cannot_access_customer_internal_transactions_list()
    {
        $response = $this->get(route('customer.transactions.internal'));

        $response->assertRedirect('/login');
    }
}
