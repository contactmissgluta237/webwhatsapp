<?php

namespace Tests\Unit;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\InternalTransaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InternalTransactionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create necessary roles
        \Spatie\Permission\Models\Role::create(['name' => 'customer']);
    }

    /** @test */
    public function it_can_create_an_internal_transaction()
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->create(['user_id' => $user->id]);

        $transaction = InternalTransaction::factory()->create([
            'wallet_id' => $wallet->id,
            'amount' => 1000,
            'transaction_type' => TransactionType::CREDIT(),
            'status' => TransactionStatus::PENDING(),
            'created_by' => $user->id,
        ]);

        $this->assertDatabaseHas('internal_transactions', [
            'id' => $transaction->id,
            'wallet_id' => $wallet->id,
            'amount' => 1000,
            'transaction_type' => TransactionType::CREDIT()->value,
            'status' => TransactionStatus::PENDING()->value,
            'created_by' => $user->id,
        ]);
    }

    /** @test */
    public function it_has_a_wallet_relationship()
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->create(['user_id' => $user->id]);
        $transaction = InternalTransaction::factory()->create(['wallet_id' => $wallet->id]);

        $this->assertInstanceOf(Wallet::class, $transaction->wallet);
        $this->assertEquals($wallet->id, $transaction->wallet->id);
    }

    /** @test */
    public function it_has_a_creator_relationship()
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->create(['user_id' => $user->id]);
        $transaction = InternalTransaction::factory()->create([
            'wallet_id' => $wallet->id,
            'created_by' => $user->id,
        ]);

        $this->assertInstanceOf(User::class, $transaction->creator);
        $this->assertEquals($user->id, $transaction->creator->id);
    }

    /** @test */
    public function it_has_a_recipient_relationship()
    {
        $sender = User::factory()->create();
        $recipient = User::factory()->create();
        $wallet = Wallet::factory()->create(['user_id' => $sender->id]);

        $transaction = InternalTransaction::factory()->create([
            'wallet_id' => $wallet->id,
            'recipient_user_id' => $recipient->id,
            'created_by' => $sender->id,
        ]);

        $this->assertInstanceOf(User::class, $transaction->recipient);
        $this->assertEquals($recipient->id, $transaction->recipient->id);
    }

    /** @test */
    public function it_can_be_a_credit_transaction()
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->create(['user_id' => $user->id]);
        $transaction = InternalTransaction::factory()->create([
            'wallet_id' => $wallet->id,
            'transaction_type' => TransactionType::CREDIT(),
            'created_by' => $user->id,
        ]);

        $this->assertTrue($transaction->isCredit());
        $this->assertFalse($transaction->isDebit());
    }

    /** @test */
    public function it_can_be_a_debit_transaction()
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->create(['user_id' => $user->id]);
        $transaction = InternalTransaction::factory()->create([
            'wallet_id' => $wallet->id,
            'transaction_type' => TransactionType::DEBIT(),
            'created_by' => $user->id,
        ]);

        $this->assertTrue($transaction->isDebit());
        $this->assertFalse($transaction->isCredit());
    }

    /** @test */
    public function it_can_be_a_completed_transaction()
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->create(['user_id' => $user->id]);
        $transaction = InternalTransaction::factory()->create([
            'wallet_id' => $wallet->id,
            'status' => TransactionStatus::COMPLETED(),
            'created_by' => $user->id,
        ]);

        $this->assertTrue($transaction->isCompleted());
        $this->assertFalse($transaction->isPending());
    }

    /** @test */
    public function it_can_be_a_pending_transaction()
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->create(['user_id' => $user->id]);
        $transaction = InternalTransaction::factory()->create([
            'wallet_id' => $wallet->id,
            'status' => TransactionStatus::PENDING(),
            'created_by' => $user->id,
        ]);

        $this->assertTrue($transaction->isPending());
        $this->assertFalse($transaction->isCompleted());
    }

    /** @test */
    public function it_can_be_a_failed_transaction()
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->create(['user_id' => $user->id]);
        $transaction = InternalTransaction::factory()->create([
            'wallet_id' => $wallet->id,
            'status' => TransactionStatus::FAILED(),
            'created_by' => $user->id,
        ]);

        $this->assertTrue($transaction->isFailed());
        $this->assertFalse($transaction->isCompleted());
        $this->assertFalse($transaction->isPending());
    }

    /** @test */
    public function scope_credits_returns_only_credit_transactions()
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->create(['user_id' => $user->id]);
        InternalTransaction::factory()->count(3)->create([
            'wallet_id' => $wallet->id,
            'transaction_type' => TransactionType::CREDIT(),
            'created_by' => $user->id,
        ]);
        InternalTransaction::factory()->count(2)->create([
            'wallet_id' => $wallet->id,
            'transaction_type' => TransactionType::DEBIT(),
            'created_by' => $user->id,
        ]);

        $credits = InternalTransaction::credits()->get();
        $this->assertCount(3, $credits);
        $credits->each(function ($transaction) {
            $this->assertTrue($transaction->isCredit());
        });
    }

    /** @test */
    public function scope_debits_returns_only_debit_transactions()
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->create(['user_id' => $user->id]);
        InternalTransaction::factory()->count(3)->create([
            'wallet_id' => $wallet->id,
            'transaction_type' => TransactionType::CREDIT(),
            'created_by' => $user->id,
        ]);
        InternalTransaction::factory()->count(2)->create([
            'wallet_id' => $wallet->id,
            'transaction_type' => TransactionType::DEBIT(),
            'created_by' => $user->id,
        ]);

        $debits = InternalTransaction::debits()->get();
        $this->assertCount(2, $debits);
        $debits->each(function ($transaction) {
            $this->assertTrue($transaction->isDebit());
        });
    }

    /** @test */
    public function scope_completed_returns_only_completed_transactions()
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->create(['user_id' => $user->id]);
        InternalTransaction::factory()->count(3)->create([
            'wallet_id' => $wallet->id,
            'status' => TransactionStatus::COMPLETED(),
            'created_by' => $user->id,
        ]);
        InternalTransaction::factory()->count(2)->create([
            'wallet_id' => $wallet->id,
            'status' => TransactionStatus::PENDING(),
            'created_by' => $user->id,
        ]);

        $completed = InternalTransaction::completed()->get();
        $this->assertCount(3, $completed);
        $completed->each(function ($transaction) {
            $this->assertTrue($transaction->isCompleted());
        });
    }
}
