<?php

declare(strict_types=1);

namespace Tests\Feature\Feature\Customer\WhatsApp;

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

        // Créer les rôles nécessaires
        \Spatie\Permission\Models\Role::create(['name' => 'customer']);
        \Spatie\Permission\Models\Role::create(['name' => 'admin']);

        $this->customer = User::factory()->create();
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
            ->assertSee('+237123456789')
            ->assertSee('Individuel')
            ->assertSee('2 non lus');
    }

    public function test_conversation_datatable_filters_by_type(): void
    {
        WhatsAppConversation::factory()->create([
            'whatsapp_account_id' => $this->account->id,
            'contact_name' => 'Individual Chat',
            'is_group' => false,
        ]);

        WhatsAppConversation::factory()->create([
            'whatsapp_account_id' => $this->account->id,
            'contact_name' => 'Group Chat',
            'is_group' => true,
        ]);

        Livewire::actingAs($this->customer)
            ->test('customer.whats-app.conversation-data-table', ['account' => $this->account])
            ->set('filterValues.is_group', '0')
            ->assertSee('Individual Chat')
            ->assertDontSee('Group Chat');
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
            ->assertSeeLivewire('customer.whats-app.conversation-view');
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

        Livewire::actingAs($this->customer)
            ->test('customer.whats-app.conversation-view', [
                'account' => $this->account,
                'conversation' => $conversation,
            ])
            ->assertSee('Hello, this is a test message')
            ->assertSee('This is a reply message');
    }

    public function test_customer_can_toggle_ai_for_conversation(): void
    {
        $conversation = WhatsAppConversation::factory()->create([
            'whatsapp_account_id' => $this->account->id,
            'is_ai_enabled' => false,
        ]);

        $this->actingAs($this->customer)
            ->post(route('customer.whatsapp.conversations.toggle-ai', [
                'account' => $this->account,
                'conversation' => $conversation,
            ]), ['enable' => '1'])
            ->assertRedirect()
            ->assertSessionHas('success', 'IA activée pour cette conversation.');

        $this->assertTrue($conversation->fresh()->is_ai_enabled);
    }

    public function test_customer_can_mark_conversation_as_read(): void
    {
        $conversation = WhatsAppConversation::factory()->create([
            'whatsapp_account_id' => $this->account->id,
            'unread_count' => 5,
        ]);

        $this->actingAs($this->customer)
            ->post(route('customer.whatsapp.conversations.mark-read', [
                'account' => $this->account,
                'conversation' => $conversation,
            ]))
            ->assertRedirect()
            ->assertSessionHas('success', 'Conversation marquée comme lue.');

        $this->assertEquals(0, $conversation->fresh()->unread_count);
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
        $conversation = WhatsAppConversation::factory()->create([
            'whatsapp_account_id' => $this->account->id,
            'unread_count' => 3,
        ]);

        Livewire::actingAs($this->customer)
            ->test('customer.whats-app.conversation-view', [
                'account' => $this->account,
                'conversation' => $conversation,
            ]);

        $this->assertEquals(0, $conversation->fresh()->unread_count);
    }

    public function test_conversation_view_shows_empty_state_when_no_messages(): void
    {
        $conversation = WhatsAppConversation::factory()->create([
            'whatsapp_account_id' => $this->account->id,
        ]);

        Livewire::actingAs($this->customer)
            ->test('customer.whats-app.conversation-view', [
                'account' => $this->account,
                'conversation' => $conversation,
            ])
            ->assertSee('Aucun message dans cette conversation');
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
