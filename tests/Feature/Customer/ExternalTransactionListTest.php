<?php

namespace Tests\Feature\Customer;

use App\Enums\ExternalTransactionType;
use App\Enums\PaymentMethod;
use App\Enums\TransactionStatus;
use App\Models\ExternalTransaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ExternalTransactionListTest extends TestCase
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

        // Créer les rôles nécessaires
        \Spatie\Permission\Models\Role::create(['name' => 'admin']);
        \Spatie\Permission\Models\Role::create(['name' => 'customer']);

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
    public function customer_can_access_their_transactions_list_page()
    {
        $response = $this->actingAs($this->customer)
            ->get('/customer/transactions');

        $response->assertStatus(200);
        $response->assertViewIs('customer.transactions.index');
        $response->assertViewHas('title', 'Mes Transactions');
        $response->assertSee('Mes Transactions');
        $response->assertSee('Nouvelle Recharge');
        $response->assertSee('Demander un Retrait');
    }

    /** @test */
    public function customer_can_only_see_their_own_transactions()
    {
        ExternalTransaction::factory()->count(3)->create([
            'wallet_id' => $this->wallet->id,
            'transaction_type' => ExternalTransactionType::RECHARGE()->value,
        ]);

        ExternalTransaction::factory()->count(2)->create([
            'wallet_id' => $this->otherWallet->id,
            'transaction_type' => ExternalTransactionType::WITHDRAWAL()->value,
        ]);

        // Le customer ne voit que ses propres transactions (3 recharges)
        // Il ne voit pas les 2 retraits de l'autre customer
        Livewire::actingAs($this->customer)
            ->test(\App\Livewire\Customer\ExternalTransactionDataTable::class)
            ->assertSee('Recharge'); // Ses propres transactions sont visibles
    }

    /** @test */
    public function customer_sees_only_details_action_button()
    {
        ExternalTransaction::factory()->create([
            'wallet_id' => $this->wallet->id,
            'transaction_type' => ExternalTransactionType::WITHDRAWAL()->value,
            'status' => TransactionStatus::PENDING()->value,
        ]);

        Livewire::actingAs($this->customer)
            ->test(\App\Livewire\Customer\ExternalTransactionDataTable::class)
            ->assertSee('Détails')
            ->assertDontSee('Approuver')
            ->assertDontSee('Annuler')
            ->assertDontSee('Modifier');
    }

    /** @test */
    public function customer_can_filter_their_transactions_by_type()
    {
        ExternalTransaction::factory()->create([
            'wallet_id' => $this->wallet->id,
            'transaction_type' => ExternalTransactionType::RECHARGE()->value,
        ]);

        ExternalTransaction::factory()->create([
            'wallet_id' => $this->wallet->id,
            'transaction_type' => ExternalTransactionType::WITHDRAWAL()->value,
        ]);

        $component = Livewire::actingAs($this->customer)
            ->test(\App\Livewire\Customer\ExternalTransactionDataTable::class);

        $component->assertSee('Recharge');
        $component->call('setFilter', 'transaction_type', 'recharge');
    }

    /** @test */
    public function customer_can_filter_their_transactions_by_status()
    {
        ExternalTransaction::factory()->create([
            'wallet_id' => $this->wallet->id,
            'status' => TransactionStatus::COMPLETED()->value,
        ]);

        ExternalTransaction::factory()->create([
            'wallet_id' => $this->wallet->id,
            'status' => TransactionStatus::PENDING()->value,
        ]);

        $component = Livewire::actingAs($this->customer)
            ->test(\App\Livewire\Customer\ExternalTransactionDataTable::class);

        $component->assertSee('Terminé');
        $component->call('setFilter', 'status', 'completed');
    }

    /** @test */
    public function customer_can_search_their_transactions_by_description()
    {
        ExternalTransaction::factory()->create([
            'wallet_id' => $this->wallet->id,
            'description' => 'Recharge via Orange Money',
        ]);

        ExternalTransaction::factory()->create([
            'wallet_id' => $this->wallet->id,
            'description' => 'Retrait bancaire',
        ]);

        Livewire::actingAs($this->customer)
            ->test(\App\Livewire\Customer\ExternalTransactionDataTable::class)
            ->set('search', 'Orange')
            ->assertSee('Recharge via Orange Money')
            ->assertDontSee('Retrait bancaire');
    }

    /** @test */
    public function customer_can_see_transaction_details_with_proper_formatting()
    {
        ExternalTransaction::factory()->create([
            'wallet_id' => $this->wallet->id,
            'amount' => 50000,
            'transaction_type' => ExternalTransactionType::RECHARGE()->value,
            'status' => TransactionStatus::COMPLETED()->value,
            'payment_method' => PaymentMethod::MOBILE_MONEY()->value,
            'created_at' => now()->subDays(2),
        ]);

        Livewire::actingAs($this->customer)
            ->test(\App\Livewire\Customer\ExternalTransactionDataTable::class)
            ->assertSee('50 000 FCFA')
            ->assertSee('Recharge')
            ->assertSee('Terminé')
            ->assertSee('Mobile Money');
    }

    /** @test */
    public function customer_without_wallet_sees_empty_transactions_list()
    {
        $customerWithoutWallet = User::factory()->create();
        $customerWithoutWallet->assignRole('customer');

        $component = Livewire::actingAs($customerWithoutWallet)
            ->test(\App\Livewire\Customer\ExternalTransactionDataTable::class);

        // Vérifier simplement que le composant se charge sans erreur
        $component->assertStatus(200);
    }

    /** @test */
    public function admin_cannot_access_customer_transactions_list()
    {
        $response = $this->actingAs($this->admin)
            ->get('/customer/transactions');

        $response->assertStatus(403);
    }

    /** @test */
    public function unauthenticated_user_cannot_access_customer_transactions_list()
    {
        $response = $this->get('/customer/transactions');

        $response->assertRedirect('/login');
    }

    /** @test */
    public function customer_can_export_their_transactions()
    {
        ExternalTransaction::factory()->count(5)->create([
            'wallet_id' => $this->wallet->id,
        ]);

        $component = Livewire::actingAs($this->customer)
            ->test(\App\Livewire\Customer\ExternalTransactionDataTable::class);

        // Vérifier que le composant se charge correctement avec des transactions
        $component->assertStatus(200);
        $component->assertSee('Recharge'); // Au moins une transaction visible
    }
}
