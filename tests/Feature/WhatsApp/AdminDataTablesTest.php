<?php

declare(strict_types=1);

namespace Tests\Feature\WhatsApp;

use App\Livewire\Admin\WhatsApp\ConversationDataTable as AdminConversationDataTable;
use App\Livewire\Admin\WhatsApp\WhatsAppAccountDataTable as AdminWhatsAppAccountDataTable;
use App\Livewire\Customer\WhatsApp\ConversationDataTable as CustomerConversationDataTable;
use App\Livewire\Customer\WhatsApp\WhatsAppAccountDataTable as CustomerWhatsAppAccountDataTable;
use App\Models\AiModel;
use App\Models\AiUsageLog;
use App\Models\User;
use App\Models\WhatsAppAccount;
use App\Models\WhatsAppConversation;
use App\Models\WhatsAppMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;
use Tests\TestCase;

class AdminDataTablesTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;
    private User $customerUser;
    private WhatsAppAccount $account;
    private WhatsAppConversation $conversation;
    private WhatsAppMessage $message;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create(['role' => 'admin']);
        $this->customerUser = User::factory()->create(['role' => 'customer']);

        $aiModel = AiModel::factory()->create();

        $this->account = WhatsAppAccount::factory()->create([
            'user_id' => $this->customerUser->id,
            'ai_model_id' => $aiModel->id,
            'agent_enabled' => true,
        ]);

        $this->conversation = WhatsAppConversation::factory()->create([
            'whatsapp_account_id' => $this->account->id,
            'chat_id' => 'test_chat_123',
        ]);

        $this->message = WhatsAppMessage::factory()->create([
            'whatsapp_conversation_id' => $this->conversation->id,
            'whatsapp_message_id' => 'msg_123',
        ]);

        // Create AI usage logs for testing
        AiUsageLog::factory()->count(3)->create([
            'user_id' => $this->customerUser->id,
            'whatsapp_account_id' => $this->account->id,
            'whatsapp_conversation_id' => $this->conversation->id,
            'whatsapp_message_id' => $this->message->id,
            'total_cost_usd' => 0.001,
            'total_cost_xaf' => 0.65,
            'total_tokens' => 150,
            'ai_model' => 'deepseek-chat',
        ]);
    }

    /** @test */
    public function admin_account_datatable_inherits_from_customer_datatable(): void
    {
        // Act & Assert
        $this->assertTrue(
            is_subclass_of(AdminWhatsAppAccountDataTable::class, CustomerWhatsAppAccountDataTable::class),
            'Admin WhatsAppAccount DataTable should inherit from Customer DataTable'
        );
    }

    /** @test */
    public function admin_conversation_datatable_inherits_from_customer_datatable(): void
    {
        // Act & Assert
        $this->assertTrue(
            is_subclass_of(AdminConversationDataTable::class, CustomerConversationDataTable::class),
            'Admin Conversation DataTable should inherit from Customer DataTable'
        );
    }

    /** @test */
    public function admin_account_datatable_displays_user_information(): void
    {
        Auth::login($this->adminUser);

        // Act
        $component = Livewire::test(AdminWhatsAppAccountDataTable::class);

        // Assert - Should display user information in columns
        $component->assertSeeInOrder([
            $this->customerUser->name,
            $this->customerUser->email,
        ]);
    }

    /** @test */
    public function admin_account_datatable_displays_ai_cost_statistics(): void
    {
        Auth::login($this->adminUser);

        // Act
        $component = Livewire::test(AdminWhatsAppAccountDataTable::class);

        // Assert - Should display AI cost information
        $expectedCost = 1.95; // 3 logs * 0.65 XAF each
        $expectedRequests = 3;
        $expectedTokens = 450; // 3 logs * 150 tokens each

        $component->assertSee(number_format($expectedCost, 0).' XAF');
        $component->assertSee($expectedRequests.' AI req.');
        $component->assertSee(number_format($expectedTokens).' tokens');
    }

    /** @test */
    public function admin_conversation_datatable_displays_user_information_when_needed(): void
    {
        Auth::login($this->adminUser);

        // Act - Test without specific user/account filters
        $component = Livewire::test(AdminConversationDataTable::class);

        // Assert - Should display user information
        $component->assertSeeInOrder([
            $this->customerUser->name,
            $this->customerUser->email,
        ]);
    }

    /** @test */
    public function admin_conversation_datatable_displays_ai_usage_statistics(): void
    {
        Auth::login($this->adminUser);

        // Act
        $component = Livewire::test(AdminConversationDataTable::class);

        // Assert - Should display AI usage statistics
        $expectedCost = 1.95; // 3 logs * 0.65 XAF each
        $expectedRequests = 3;
        $expectedTokens = 450; // 3 logs * 150 tokens each

        $component->assertSee(number_format($expectedCost, 0).' XAF');
        $component->assertSee($expectedRequests.' req.');
        $component->assertSee(number_format($expectedTokens));
    }

    /** @test */
    public function admin_datatables_have_additional_filters_not_present_in_customer_version(): void
    {
        Auth::login($this->adminUser);

        // Test Account DataTable
        $accountComponent = Livewire::test(AdminWhatsAppAccountDataTable::class);
        $accountComponent->assertSee('All users'); // User filter
        $accountComponent->assertSee('All accounts'); // Should still have base filters
        $accountComponent->assertSee('With AI activity'); // AI activity filter

        // Test Conversation DataTable
        $conversationComponent = Livewire::test(AdminConversationDataTable::class);
        $conversationComponent->assertSee('All users'); // User filter
        $conversationComponent->assertSee('With AI activity'); // AI activity filter
        $conversationComponent->assertSee('Low (< 500 XAF)'); // Cost range filter
    }

    /** @test */
    public function admin_datatables_can_filter_by_user(): void
    {
        Auth::login($this->adminUser);

        // Create another user with WhatsApp account for testing filtering
        $anotherUser = User::factory()->create(['role' => 'customer']);
        $anotherAccount = WhatsAppAccount::factory()->create([
            'user_id' => $anotherUser->id,
        ]);

        // Act - Filter by specific user
        $component = Livewire::test(AdminWhatsAppAccountDataTable::class)
            ->set('filters.user_id', $this->customerUser->id);

        // Assert - Should only show accounts for the filtered user
        $component->assertSee($this->customerUser->name);
        $component->assertDontSee($anotherUser->name);
    }

    /** @test */
    public function admin_datatables_can_filter_by_ai_activity(): void
    {
        Auth::login($this->adminUser);

        // Create account without AI usage for testing
        $accountWithoutAI = WhatsAppAccount::factory()->create([
            'user_id' => $this->customerUser->id,
            'session_name' => 'no_ai_account',
        ]);

        // Act - Filter for accounts with AI activity
        $component = Livewire::test(AdminWhatsAppAccountDataTable::class)
            ->set('filters.has_usage', '1');

        // Assert - Should show account with AI activity, hide account without
        $component->assertSee($this->account->session_name);
        $component->assertDontSee($accountWithoutAI->session_name);
    }

    /** @test */
    public function admin_datatables_can_filter_by_cost_range(): void
    {
        Auth::login($this->adminUser);

        // Create high-cost AI usage logs
        AiUsageLog::factory()->count(10)->create([
            'user_id' => $this->customerUser->id,
            'whatsapp_account_id' => $this->account->id,
            'whatsapp_conversation_id' => $this->conversation->id,
            'total_cost_xaf' => 300, // High cost per usage
        ]);

        // Act - Filter for high cost range
        $component = Livewire::test(AdminWhatsAppAccountDataTable::class)
            ->set('filters.cost_range', 'high');

        // Assert - Should show accounts with high costs
        $component->assertSee($this->account->session_name);
    }

    /** @test */
    public function customer_datatables_do_not_show_admin_specific_features(): void
    {
        Auth::login($this->customerUser);

        // Test Customer Account DataTable
        $accountComponent = Livewire::test(CustomerWhatsAppAccountDataTable::class, [
            'user' => $this->customerUser,
        ]);

        // Assert - Should NOT have user filters or admin-specific content
        $accountComponent->assertDontSee('All users');
        $accountComponent->assertDontSee('With AI activity');

        // Test Customer Conversation DataTable
        $conversationComponent = Livewire::test(CustomerConversationDataTable::class, [
            'user' => $this->customerUser,
        ]);

        // Assert - Should NOT have user information or admin filters
        $conversationComponent->assertDontSee('All users');
        $conversationComponent->assertDontSee('Cost Range');
    }

    /** @test */
    public function admin_datatables_handle_empty_results_gracefully(): void
    {
        Auth::login($this->adminUser);

        // Remove all data
        AiUsageLog::truncate();
        WhatsAppMessage::truncate();
        WhatsAppConversation::truncate();
        WhatsAppAccount::truncate();

        // Act
        $accountComponent = Livewire::test(AdminWhatsAppAccountDataTable::class);
        $conversationComponent = Livewire::test(AdminConversationDataTable::class);

        // Assert - Should handle empty state gracefully
        $accountComponent->assertSee('No WhatsApp account found in the system.');
        $conversationComponent->assertSee('No conversation found in the system.');
    }

    /** @test */
    public function admin_datatables_show_correct_empty_message_when_filtered_by_user(): void
    {
        Auth::login($this->adminUser);

        // Create a user with no WhatsApp accounts
        $userWithoutAccounts = User::factory()->create(['role' => 'customer']);

        // Act
        $component = Livewire::test(AdminWhatsAppAccountDataTable::class, [
            'user' => $userWithoutAccounts,
        ]);

        // Assert - Should show user-specific empty message
        $component->assertSee('No WhatsApp account found for this user.');
    }
}
