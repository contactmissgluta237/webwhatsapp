<?php

declare(strict_types=1);

namespace Tests\Feature\Feature\Customer\WhatsApp;

use App\Models\User;
use App\Models\WhatsAppAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class WhatsAppAccountDataTableTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        // Créer les rôles nécessaires
        \Spatie\Permission\Models\Role::create(['name' => 'customer']);
        \Spatie\Permission\Models\Role::create(['name' => 'admin']);

        $this->customer = User::factory()->create();
        $this->customer->assignRole('customer');
    }

    #[Test]
    public function test_customer_can_view_whatsapp_accounts_datatable(): void
    {
        // Créer des comptes WhatsApp pour le client
        $accounts = WhatsAppAccount::factory()->count(3)->create([
            'user_id' => $this->customer->id,
        ]);

        $this->actingAs($this->customer)
            ->get(route('customer.whatsapp.index'))
            ->assertOk()
            ->assertSeeLivewire('customer.whats-app.whats-app-account-data-table');
    }

    #[Test]
    public function test_datatable_displays_account_information_correctly(): void
    {
        $account = WhatsAppAccount::factory()->create([
            'user_id' => $this->customer->id,
            'session_name' => 'Test Account',
            'phone_number' => '+237123456789',
            'status' => 'connected',
            'agent_enabled' => true,
        ]);

        Livewire::actingAs($this->customer)
            ->test('customer.whats-app.whats-app-account-data-table')
            ->assertSee('Test Account')
            ->assertSee('+237123456789')
            ->assertSee('Connected')
            ->assertSee('Active');
    }

    #[Test]
    public function test_datatable_shows_empty_state_when_no_accounts(): void
    {
        Livewire::actingAs($this->customer)
            ->test('customer.whats-app.whats-app-account-data-table')
            ->assertSee('No WhatsApp session found');
    }

    #[Test]
    public function test_datatable_filters_by_status(): void
    {
        WhatsAppAccount::factory()->create([
            'user_id' => $this->customer->id,
            'session_name' => 'Connected Account',
            'status' => 'connected',
        ]);

        WhatsAppAccount::factory()->create([
            'user_id' => $this->customer->id,
            'session_name' => 'Disconnected Account',
            'status' => 'disconnected',
        ]);

        Livewire::actingAs($this->customer)
            ->test('customer.whats-app.whats-app-account-data-table')
            ->call('setFilter', 'status', 'connected')
            ->assertSee('Connected Account')
            ->assertDontSee('Disconnected Account');
    }

    #[Test]
    public function test_datatable_filters_by_ai_status(): void
    {
        WhatsAppAccount::factory()->withAi()->create([
            'user_id' => $this->customer->id,
            'session_name' => 'AI Enabled',
        ]);

        WhatsAppAccount::factory()->create([
            'user_id' => $this->customer->id,
            'session_name' => 'AI Disabled',
            'agent_enabled' => false,
        ]);

        Livewire::actingAs($this->customer)
            ->test('customer.whats-app.whats-app-account-data-table')
            ->call('setFilter', 'agent_enabled', '1')
            ->assertSee('AI Enabled')
            ->assertDontSee('AI Disabled');
    }

    #[Test]
    public function test_datatable_search_functionality(): void
    {
        WhatsAppAccount::factory()->create([
            'user_id' => $this->customer->id,
            'session_name' => 'Findable Account',
        ]);

        WhatsAppAccount::factory()->create([
            'user_id' => $this->customer->id,
            'session_name' => 'Other Account',
        ]);

        Livewire::actingAs($this->customer)
            ->test('customer.whats-app.whats-app-account-data-table')
            ->set('search', 'Findable')
            ->assertSee('Findable Account')
            ->assertDontSee('Other Account');
    }

    #[Test]
    public function test_actions_dropdown_contains_conversations_link(): void
    {
        $account = WhatsAppAccount::factory()->create([
            'user_id' => $this->customer->id,
        ]);

        $this->actingAs($this->customer)
            ->get(route('customer.whatsapp.index'))
            ->assertOk()
            ->assertSee(route('customer.whatsapp.conversations.index', $account->id));
    }

    #[Test]
    public function test_customer_cannot_see_other_users_accounts(): void
    {
        $otherUser = User::factory()->create();
        $otherUser->assignRole('customer');

        WhatsAppAccount::factory()->create([
            'user_id' => $otherUser->id,
            'session_name' => 'Other User Account',
        ]);

        $myAccount = WhatsAppAccount::factory()->create([
            'user_id' => $this->customer->id,
            'session_name' => 'My Account',
        ]);

        Livewire::actingAs($this->customer)
            ->test('customer.whats-app.whats-app-account-data-table')
            ->assertSee('My Account')
            ->assertDontSee('Other User Account');
    }
}
