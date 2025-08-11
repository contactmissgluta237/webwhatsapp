<?php

namespace Tests\Feature\Admin;

use App\Enums\ExternalTransactionType;
use App\Enums\TransactionStatus;
use App\Events\ExternalTransactionApprovedEvent;
use App\Mail\ExternalTransactionApprovedMail;
use App\Models\ExternalTransaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ApproveTransactionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $customer;
    private Wallet $wallet;

    protected function setUp(): void
    {
        parent::setUp();

        \Spatie\Permission\Models\Role::create(['name' => 'admin']);
        \Spatie\Permission\Models\Role::create(['name' => 'customer']);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->customer = User::factory()->create();
        $this->customer->assignRole('customer');

        $this->wallet = Wallet::factory()->create(['user_id' => $this->customer->id]);
    }

    /** @test */
    public function admin_can_approve_a_pending_withdrawal_transaction()
    {
        Mail::fake();
        Event::fake();

        $transaction = ExternalTransaction::factory()->create([
            'wallet_id' => $this->wallet->id,
            'transaction_type' => ExternalTransactionType::WITHDRAWAL(),
            'status' => TransactionStatus::PENDING(),
            'approved_by' => null,
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.transactions.externals.approve', $transaction));

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Transaction approved successfully.');

        $this->assertDatabaseHas('external_transactions', [
            'id' => $transaction->id,
            'status' => TransactionStatus::COMPLETED()->value,
            'approved_by' => $this->admin->id,
        ]);

        Event::assertDispatched(ExternalTransactionApprovedEvent::class, function ($event) use ($transaction) {
            return $event->transaction->id === $transaction->id;
        });

        Mail::assertSent(ExternalTransactionApprovedMail::class, function ($mail) use ($transaction) {
            return $mail->hasTo($transaction->wallet->user->email);
        });
    }
}
