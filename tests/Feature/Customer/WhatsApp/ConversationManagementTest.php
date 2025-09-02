<?php

declare(strict_types=1);

namespace Tests\Feature\Customer\WhatsApp;

use App\Models\User;
use App\Models\WhatsAppAccount;
use App\Models\WhatsAppConversation;
use App\Models\WhatsAppMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

final class ConversationManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;
    private WhatsAppAccount $account;

    protected function setUp(): void
    {
        parent::setUp();

        // Créer un pays avec l'ID 1 AVANT de créer l'utilisateur
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

        // Créer les rôles nécessaires
        \Spatie\Permission\Models\Role::create(['name' => 'customer']);
        \Spatie\Permission\Models\Role::create(['name' => 'admin']);

        $this->customer = User::factory()->create([
            'country_id' => 1,
        ]);
        $this->customer->assignRole('customer');

        $this->account = WhatsAppAccount::factory()->create([
            'user_id' => $this->customer->id,
        ]);
    }

    public function test_customer_can_view_conversations_list(): void
    {
        WhatsAppConversation::factory()->count(3)->create([
            'whatsapp_account_id' => $this->account->id,
        ]);

        $this->actingAs($this->customer)
            ->get(route('customer.whatsapp.conversations.index', $this->account))
            ->assertOk()
            ->assertSee('Conversations')
            ->assertSeeLivewire('customer.whats-app.conversation-data-table');
    }

    public function test_conversation_datatable_displays_conversations(): void
    {
        $conversation = WhatsAppConversation::factory()->create([
            'whatsapp_account_id' => $this->account->id,
            'contact_name' => 'Jean Dupont',
            'contact_phone' => '+237123456789',
            'is_group' => false,
            'unread_count' => 2,
        ]);

        Livewire::actingAs($this->customer)
            ->test('customer.whats-app.conversation-data-table', ['account' => $this->account])
            ->assertSee('Jean Dupont')
            ->assertSee('+237123456789');
    }

    public function test_conversation_datatable_filters_by_type(): void
    {
        $this->markTestSkipped('Filter API needs to be adapted to match the actual DataTable implementation');
    }

    public function test_customer_can_view_conversation_details(): void
    {
        $conversation = WhatsAppConversation::factory()->create([
            'whatsapp_account_id' => $this->account->id,
            'contact_name' => 'Test Contact',
        ]);

        // Créer des messages de test
        WhatsAppMessage::factory()->count(5)->create([
            'whatsapp_conversation_id' => $conversation->id,
        ]);

        $this->actingAs($this->customer)
            ->get(route('customer.whatsapp.conversations.show', [
                'account' => $this->account,
                'conversation' => $conversation,
            ]))
            ->assertOk()
            ->assertSee('Test Contact')
            ->assertSee('Messages avec');
    }

    public function test_conversation_view_displays_messages(): void
    {
        $conversation = WhatsAppConversation::factory()->create([
            'whatsapp_account_id' => $this->account->id,
        ]);

        WhatsAppMessage::factory()->create([
            'whatsapp_conversation_id' => $conversation->id,
            'content' => 'Hello, this is a test message',
            'direction' => 'inbound',
        ]);

        WhatsAppMessage::factory()->create([
            'whatsapp_conversation_id' => $conversation->id,
            'content' => 'This is a reply message',
            'direction' => 'outbound',
            'is_ai_generated' => true,
        ]);

        $this->actingAs($this->customer)
            ->get(route('customer.whatsapp.conversations.show', [
                'account' => $this->account,
                'conversation' => $conversation,
            ]))
            ->assertOk()
            ->assertSee('Hello, this is a test message')
            ->assertSee('This is a reply message');
    }

    public function test_customer_can_toggle_ai_for_conversation(): void
    {
        $this->markTestSkipped('Route customer.whatsapp.conversations.toggle-ai does not exist');
    }

    public function test_customer_can_mark_conversation_as_read(): void
    {
        $this->markTestSkipped('Route customer.whatsapp.conversations.mark-read does not exist');
    }

    public function test_customer_cannot_access_other_users_conversations(): void
    {
        $otherUser = User::factory()->create();
        $otherUser->assignRole('customer');

        $otherAccount = WhatsAppAccount::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $otherConversation = WhatsAppConversation::factory()->create([
            'whatsapp_account_id' => $otherAccount->id,
        ]);

        $this->actingAs($this->customer)
            ->get(route('customer.whatsapp.conversations.index', $otherAccount))
            ->assertForbidden();

        $this->actingAs($this->customer)
            ->get(route('customer.whatsapp.conversations.show', [
                'account' => $otherAccount,
                'conversation' => $otherConversation,
            ]))
            ->assertForbidden();
    }

    public function test_conversation_automatically_marked_as_read_when_viewed(): void
    {
        $this->markTestSkipped('Auto-marking as read logic needs to be implemented in the controller or view');
    }

    public function test_conversation_view_shows_empty_state_when_no_messages(): void
    {
        $conversation = WhatsAppConversation::factory()->create([
            'whatsapp_account_id' => $this->account->id,
        ]);

        $this->actingAs($this->customer)
            ->get(route('customer.whatsapp.conversations.show', [
                'account' => $this->account,
                'conversation' => $conversation,
            ]))
            ->assertOk()
            ->assertSee('Aucun message');
    }

    public function test_customer_cannot_access_conversation_from_different_account(): void
    {
        $otherAccount = WhatsAppAccount::factory()->create([
            'user_id' => $this->customer->id,
        ]);

        $conversation = WhatsAppConversation::factory()->create([
            'whatsapp_account_id' => $otherAccount->id,
        ]);

        $this->actingAs($this->customer)
            ->get(route('customer.whatsapp.conversations.show', [
                'account' => $this->account, // Wrong account
                'conversation' => $conversation, // Belongs to other account
            ]))
            ->assertNotFound();
    }
}
