<?php

namespace Tests\Unit;

use App\Models\ExternalTransaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserMetricsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_calculates_total_recharged_amount_correctly(): void
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->create(['user_id' => $user->id]);

        ExternalTransaction::factory()->create([
            'wallet_id' => $wallet->id,
            'transaction_type' => 'recharge',
            'amount' => 1000,
            'status' => 'completed',
        ]);
        ExternalTransaction::factory()->create([
            'wallet_id' => $wallet->id,
            'transaction_type' => 'recharge',
            'amount' => 2500,
            'status' => 'completed',
        ]);
        // Should not be included
        ExternalTransaction::factory()->create([
            'wallet_id' => $wallet->id,
            'transaction_type' => 'withdrawal',
            'amount' => 500,
            'status' => 'completed',
        ]);
        ExternalTransaction::factory()->create([
            'wallet_id' => $wallet->id,
            'transaction_type' => 'recharge',
            'amount' => 5000,
            'status' => 'pending', // Should not be included if only completed are counted
        ]);

        $this->assertEquals(3500, $user->totalRecharged());
    }

    #[Test]
    public function it_calculates_total_withdrawn_amount_correctly(): void
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->create(['user_id' => $user->id]);

        ExternalTransaction::factory()->create([
            'wallet_id' => $wallet->id,
            'transaction_type' => 'withdrawal',
            'amount' => 700,
            'status' => 'completed',
        ]);
        ExternalTransaction::factory()->create([
            'wallet_id' => $wallet->id,
            'transaction_type' => 'withdrawal',
            'amount' => 1300,
            'status' => 'completed',
        ]);
        // Should not be included
        ExternalTransaction::factory()->create([
            'wallet_id' => $wallet->id,
            'transaction_type' => 'recharge',
            'amount' => 1000,
            'status' => 'completed',
        ]);
        ExternalTransaction::factory()->create([
            'wallet_id' => $wallet->id,
            'transaction_type' => 'withdrawal',
            'amount' => 2000,
            'status' => 'pending', // Should not be included if only completed are counted
        ]);

        $this->assertEquals(2000, $user->totalWithdrawn());
    }

    #[Test]
    public function it_returns_zero_for_total_recharged_if_no_wallet(): void
    {
        $user = User::factory()->create();
        $this->assertEquals(0, $user->totalRecharged());
    }

    #[Test]
    public function it_returns_zero_for_total_withdrawn_if_no_wallet(): void
    {
        $user = User::factory()->create();
        $this->assertEquals(0, $user->totalWithdrawn());
    }

    #[Test]
    public function it_returns_zero_for_total_referral_earnings_by_default(): void
    {
        $user = User::factory()->create();
        $this->assertEquals(0.0, $user->totalReferralEarnings());
    }

    #[Test]
    public function a_user_can_have_referred_users(): void
    {
        $referrer = User::factory()->create();
        $referred1 = User::factory()->create(['referrer_id' => $referrer->id]);
        $referred2 = User::factory()->create(['referrer_id' => $referrer->id]);

        $this->assertCount(2, $referrer->referredUsers);
        $this->assertTrue($referrer->referredUsers->contains($referred1));
        $this->assertTrue($referrer->referredUsers->contains($referred2));
    }

    #[Test]
    public function a_user_can_have_a_referrer(): void
    {
        $referrer = User::factory()->create();
        $referred = User::factory()->create(['referrer_id' => $referrer->id]);

        $this->assertTrue($referred->referrer->is($referrer));
    }

    #[Test]
    public function a_user_without_referrer_has_null_referrer(): void
    {
        $user = User::factory()->create(['referrer_id' => null]);
        $this->assertNull($user->referrer);
    }
}
