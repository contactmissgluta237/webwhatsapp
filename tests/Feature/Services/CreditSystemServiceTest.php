<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\InternalTransaction;
use App\Models\User;
use App\Models\Wallet;
use App\Services\CreditSystemService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CreditSystemServiceTest extends TestCase
{
    use RefreshDatabase;

    private CreditSystemService $creditSystemService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->creditSystemService = app(CreditSystemService::class);
    }

    /** @test */
    public function it_can_check_if_user_has_enough_credit(): void
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->create([
            'user_id' => $user->id,
            'balance' => 100.00,
        ]);

        $this->assertTrue($this->creditSystemService->hasEnoughCredit($user));
    }

    /** @test */
    public function it_returns_false_when_user_has_insufficient_credit(): void
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->create([
            'user_id' => $user->id,
            'balance' => 10.00, // Less than default message cost
        ]);

        $this->assertFalse($this->creditSystemService->hasEnoughCredit($user));
    }

    /** @test */
    public function it_returns_false_when_user_has_no_wallet(): void
    {
        $user = User::factory()->create();

        $this->assertFalse($this->creditSystemService->hasEnoughCredit($user));
    }

    /** @test */
    public function it_can_deduct_message_cost_successfully(): void
    {
        $user = User::factory()->create();
        $initialBalance = 100.00;
        $wallet = Wallet::factory()->create([
            'user_id' => $user->id,
            'balance' => $initialBalance,
        ]);

        $messageCost = $this->creditSystemService->getMessageCost();
        $result = $this->creditSystemService->deductMessageCost($user, 'Test message');

        $this->assertTrue($result);
        
        $wallet->refresh();
        $this->assertEquals($initialBalance - $messageCost, $wallet->balance);

        // Vérifier qu'une transaction a été créée
        $this->assertDatabaseHas('internal_transactions', [
            'wallet_id' => $wallet->id,
            'amount' => $messageCost,
            'transaction_type' => TransactionType::DEBIT()->value,
            'status' => TransactionStatus::COMPLETED()->value,
            'created_by' => $user->id,
        ]);
    }

    /** @test */
    public function it_fails_to_deduct_when_insufficient_balance(): void
    {
        $user = User::factory()->create();
        $initialBalance = 10.00; // Less than message cost
        $wallet = Wallet::factory()->create([
            'user_id' => $user->id,
            'balance' => $initialBalance,
        ]);

        $result = $this->creditSystemService->deductMessageCost($user, 'Test message');

        $this->assertFalse($result);
        
        $wallet->refresh();
        $this->assertEquals($initialBalance, $wallet->balance); // Balance unchanged

        // Vérifier qu'aucune transaction n'a été créée
        $this->assertDatabaseMissing('internal_transactions', [
            'wallet_id' => $wallet->id,
            'transaction_type' => TransactionType::DEBIT()->value,
        ]);
    }

    /** @test */
    public function it_fails_to_deduct_when_user_has_no_wallet(): void
    {
        $user = User::factory()->create();

        $result = $this->creditSystemService->deductMessageCost($user, 'Test message');

        $this->assertFalse($result);
    }

    /** @test */
    public function it_can_get_message_cost_from_config(): void
    {
        $cost = $this->creditSystemService->getMessageCost();

        $this->assertIsFloat($cost);
        $this->assertGreaterThanOrEqual(0, $cost);
    }

    /** @test */
    public function it_can_get_user_balance(): void
    {
        $user = User::factory()->create();
        $balance = 150.50;
        $wallet = Wallet::factory()->create([
            'user_id' => $user->id,
            'balance' => $balance,
        ]);

        $userBalance = $this->creditSystemService->getUserBalance($user);

        $this->assertEquals($balance, $userBalance);
    }

    /** @test */
    public function it_returns_zero_balance_for_user_without_wallet(): void
    {
        $user = User::factory()->create();

        $userBalance = $this->creditSystemService->getUserBalance($user);

        $this->assertEquals(0.0, $userBalance);
    }

    /** @test */
    public function it_can_check_if_user_can_afford_multiple_messages(): void
    {
        $user = User::factory()->create();
        $messageCost = $this->creditSystemService->getMessageCost();
        $wallet = Wallet::factory()->create([
            'user_id' => $user->id,
            'balance' => $messageCost * 3, // Can afford 3 messages
        ]);

        $this->assertTrue($this->creditSystemService->canAffordMessages($user, 3));
        $this->assertTrue($this->creditSystemService->canAffordMessages($user, 2));
        $this->assertFalse($this->creditSystemService->canAffordMessages($user, 4));
    }

    /** @test */
    public function it_creates_transaction_with_correct_description(): void
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->create([
            'user_id' => $user->id,
            'balance' => 100.00,
        ]);

        $messageContext = 'Session: test_session_123';
        $this->creditSystemService->deductMessageCost($user, $messageContext);

        $this->assertDatabaseHas('internal_transactions', [
            'wallet_id' => $wallet->id,
            'description' => "Déduction coût message IA - {$messageContext}",
        ]);
    }

    /** @test */
    public function it_handles_update_message_cost_validation(): void
    {
        $result = $this->creditSystemService->updateMessageCost(-10.0);
        $this->assertFalse($result);

        $result = $this->creditSystemService->updateMessageCost(50.0);
        $this->assertTrue($result);
    }
}