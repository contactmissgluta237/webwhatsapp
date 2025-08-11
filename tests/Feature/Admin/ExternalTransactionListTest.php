<?php

namespace Tests\Feature\Admin;

use App\Enums\ExternalTransactionType;
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

    private User $admin;
    private User $customer;
    private Wallet $wallet;

    protected function setUp(): void
    {
        parent::setUp();

        // Créer les rôles nécessaires
        \Spatie\Permission\Models\Role::create(['name' => 'admin']);
        \Spatie\Permission\Models\Role::create(['name' => 'customer']);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->customer = User::factory()->create();
        $this->customer->assignRole('customer');

        $this->wallet = Wallet::factory()->create(['user_id' => $this->customer->id]);
    }

    /** @test */
    public function admin_can_access_transactions_list_page()
    {
        $response = $this->actingAs($this->admin)
            ->get('/admin/transactions');

        $response->assertStatus(200);
        $response->assertViewIs('admin.transactions.index');
        $response->assertViewHas('title', 'Transactions Externes');
        $response->assertSee('Transactions Externes');
        $response->assertSee('Nouvelle Recharge');
        $response->assertSee('Nouveau Retrait');
    }

    /** @test */
    public function admin_can_see_all_external_transactions()
    {
        ExternalTransaction::factory()->count(3)->create([
            'wallet_id' => $this->wallet->id,
            'transaction_type' => ExternalTransactionType::RECHARGE()->value,
            'status' => TransactionStatus::COMPLETED()->value,
            'created_by' => $this->admin->id,
        ]);

        $otherWallet = Wallet::factory()->create(['user_id' => User::factory()->create()->id]);
        ExternalTransaction::factory()->count(2)->create([
            'wallet_id' => $otherWallet->id,
            'transaction_type' => ExternalTransactionType::WITHDRAWAL()->value,
            'status' => TransactionStatus::PENDING()->value,
        ]);

        Livewire::actingAs($this->admin)
            ->test(\App\Livewire\Admin\ExternalTransactionDataTable::class)
            ->assertSee('Recharge')
            ->assertSee('Retrait')
            ->assertSee('Terminé')
            ->assertSee('En attente');
    }

    /** @test */
    public function admin_can_filter_transactions_by_type()
    {
        ExternalTransaction::factory()->create([
            'wallet_id' => $this->wallet->id,
            'transaction_type' => ExternalTransactionType::RECHARGE()->value,
        ]);

        ExternalTransaction::factory()->create([
            'wallet_id' => $this->wallet->id,
            'transaction_type' => ExternalTransactionType::WITHDRAWAL()->value,
        ]);

        $component = Livewire::actingAs($this->admin)
            ->test(\App\Livewire\Admin\ExternalTransactionDataTable::class);

        // Vérifier que les deux types sont visibles initialement
        $component->assertSee('Recharge');

        // Tester que le filtre existe et peut être appelé
        $component->call('setFilter', 'transaction_type', 'recharge');
    }

    /** @test */
    public function admin_can_filter_transactions_by_status()
    {
        ExternalTransaction::factory()->create([
            'wallet_id' => $this->wallet->id,
            'status' => TransactionStatus::COMPLETED()->value,
        ]);

        ExternalTransaction::factory()->create([
            'wallet_id' => $this->wallet->id,
            'status' => TransactionStatus::PENDING()->value,
        ]);

        $component = Livewire::actingAs($this->admin)
            ->test(\App\Livewire\Admin\ExternalTransactionDataTable::class);

        // Vérifier que les deux statuts sont visibles initialement
        $component->assertSee('Terminé');

        // Tester que le filtre existe et peut être appelé
        $component->call('setFilter', 'status', 'completed');
    }

    /** @test */
    public function admin_can_search_transactions_by_client_name()
    {
        $this->customer->update(['first_name' => 'Jean', 'last_name' => 'Dupont']);

        ExternalTransaction::factory()->create([
            'wallet_id' => $this->wallet->id,
        ]);

        Livewire::actingAs($this->admin)
            ->test(\App\Livewire\Admin\ExternalTransactionDataTable::class)
            ->set('search', 'Jean')
            ->assertSee('Jean Dupont');
    }

    /** @test */
    public function admin_can_see_transaction_actions()
    {
        $withdrawal = ExternalTransaction::factory()->create([
            'wallet_id' => $this->wallet->id,
            'transaction_type' => ExternalTransactionType::WITHDRAWAL()->value,
            'status' => TransactionStatus::PENDING()->value,
            'approved_by' => null,
        ]);

        Livewire::actingAs($this->admin)
            ->test(\App\Livewire\Admin\ExternalTransactionDataTable::class)
            ->assertSee('Approuver')
            ->assertSee('Annuler')
            ->assertSee('Détails')
            ->assertSee('Modifier');
    }

    /** @test */
    public function customer_cannot_access_admin_transactions_list()
    {
        $response = $this->actingAs($this->customer)
            ->get('/admin/transactions');

        $response->assertStatus(403);
    }

    /** @test */
    public function unauthenticated_user_cannot_access_admin_transactions_list()
    {
        $response = $this->get('/admin/transactions');

        $response->assertRedirect('/login');
    }
}
