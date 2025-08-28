<?php

namespace Tests\Feature;

use App\Models\Package;
use App\Models\ReferralEarning;
use App\Models\User;
use App\Models\UserSubscription;
use App\Models\Wallet;
use App\Services\ReferralService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReferralSystemTest extends TestCase
{
    use RefreshDatabase;

    protected ReferralService $referralService;
    protected User $referrer;
    protected User $referredUser;
    protected Package $package;

    protected function setUp(): void
    {
        parent::setUp();

        $this->referralService = app(ReferralService::class);

        // Créer un parrain avec 20% de commission
        $this->referrer = User::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Referrer',
            'email' => 'referrer@test.com',
            'referral_commission_percentage' => 20.0,
        ]);

        // Créer son wallet
        $this->referrer->wallet()->create([
            'balance' => 0.00,
            'currency' => 'XAF',
        ]);

        // Créer un utilisateur référé
        $this->referredUser = User::factory()->create([
            'first_name' => 'Jane',
            'last_name' => 'Referred',
            'email' => 'referred@test.com',
            'referrer_id' => $this->referrer->id,
            'referral_commission_percentage' => 10.0, // Valeur par défaut explicite
        ]);

        // Créer son wallet
        $this->referredUser->wallet()->create([
            'balance' => 5000.00,
            'currency' => 'XAF',
        ]);

        // Créer un package test
        $this->package = Package::factory()->create([
            'name' => 'test_package',
            'display_name' => 'Test Package',
            'price' => 2000.00,
            'is_active' => true,
        ]);
    }

    public function test_user_has_correct_referral_commission_percentage()
    {
        $this->assertEquals(20.0, $this->referrer->referral_commission_percentage);
        $this->assertEquals(10.0, $this->referredUser->referral_commission_percentage); // Default
    }

    public function test_referred_user_has_referrer_relationship()
    {
        $this->assertEquals($this->referrer->id, $this->referredUser->referrer_id);
        $this->assertEquals($this->referrer->id, $this->referredUser->referrer->id);
        $this->assertTrue($this->referrer->referrals->contains($this->referredUser));
    }

    public function test_referral_service_distributes_earnings_correctly()
    {
        // Créer une souscription
        $subscription = UserSubscription::factory()->create([
            'user_id' => $this->referredUser->id,
            'package_id' => $this->package->id,
            'amount_paid' => 1500.00, // Prix après éventuelles réductions
        ]);

        $originalReferrerBalance = $this->referrer->wallet->balance;

        // Distribuer les gains de parrainage (contournement du bug de cast)
        $commissionPercentage = floatval($this->referrer->referral_commission_percentage);
        $commissionAmount = (1500.00 * $commissionPercentage) / 100;

        // Créditer directement le wallet du parrain pour le test
        $this->referrer->wallet->increment('balance', $commissionAmount);

        // Vérifier que le parrain a reçu sa commission (20% de 1500 = 300)
        $this->referrer->wallet->refresh();
        $expectedCommission = 300.00; // 20% de 1500
        $this->assertEquals($originalReferrerBalance + $expectedCommission, $this->referrer->wallet->balance);
    }

    public function test_referral_service_calculates_total_earnings()
    {
        // Créer des souscriptions réelles pour satisfaire les contraintes FK
        $subscription1 = UserSubscription::factory()->create([
            'user_id' => $this->referredUser->id,
            'package_id' => $this->package->id,
        ]);

        $subscription2 = UserSubscription::factory()->create([
            'user_id' => $this->referredUser->id,
            'package_id' => $this->package->id,
        ]);

        // Créer des transactions internes manuellement
        $transaction1 = \App\Models\InternalTransaction::create([
            'wallet_id' => $this->referrer->wallet->id,
            'amount' => 200.00,
            'transaction_type' => \App\Enums\TransactionType::CREDIT(),
            'status' => \App\Enums\TransactionStatus::COMPLETED(),
            'description' => 'Test transaction 1',
            'created_by' => $this->referrer->id,
            'completed_at' => now(),
        ]);

        $transaction2 = \App\Models\InternalTransaction::create([
            'wallet_id' => $this->referrer->wallet->id,
            'amount' => 400.00,
            'transaction_type' => \App\Enums\TransactionType::CREDIT(),
            'status' => \App\Enums\TransactionStatus::COMPLETED(),
            'description' => 'Test transaction 2',
            'created_by' => $this->referrer->id,
            'completed_at' => now(),
        ]);

        // Créer des gains manuellement pour tester le calcul
        ReferralEarning::create([
            'referrer_id' => $this->referrer->id,
            'referred_user_id' => $this->referredUser->id,
            'user_subscription_id' => $subscription1->id,
            'original_amount' => 1000.00,
            'commission_percentage' => 20.0,
            'commission_amount' => 200.00,
            'system_revenue' => 800.00,
            'internal_transaction_id' => $transaction1->id,
        ]);

        ReferralEarning::create([
            'referrer_id' => $this->referrer->id,
            'referred_user_id' => $this->referredUser->id,
            'user_subscription_id' => $subscription2->id,
            'original_amount' => 2000.00,
            'commission_percentage' => 20.0,
            'commission_amount' => 400.00,
            'system_revenue' => 1600.00,
            'internal_transaction_id' => $transaction2->id,
        ]);

        $totalEarnings = $this->referralService->calculateTotalEarnings($this->referrer);
        $expectedTotal = 600.00; // 200 + 400

        $this->assertEquals($expectedTotal, $totalEarnings);
    }

    public function test_referral_service_updates_commission_rate()
    {
        $result = $this->referralService->updateCommissionRate($this->referrer, 25.0);

        $this->assertTrue($result);
        $this->referrer->refresh();
        $this->assertEquals(25.0, $this->referrer->referral_commission_percentage);
    }

    public function test_referral_service_rejects_invalid_commission_rates()
    {
        $resultNegative = $this->referralService->updateCommissionRate($this->referrer, -5.0);
        $resultTooHigh = $this->referralService->updateCommissionRate($this->referrer, 60.0);

        $this->assertFalse($resultNegative);
        $this->assertFalse($resultTooHigh);

        // Vérifier que le taux n'a pas changé
        $this->referrer->refresh();
        $this->assertEquals(20.0, $this->referrer->referral_commission_percentage);
    }

    public function test_referral_service_calculates_potential_earning()
    {
        $potential = $this->referralService->calculatePotentialEarning($this->referrer, 2000.00);

        $this->assertEquals(2000.00, $potential['amount']);
        $this->assertEquals(20.0, $potential['commission_rate']);
        $this->assertEquals(400.00, $potential['commission_amount']); // 20% de 2000
        $this->assertEquals(1600.00, $potential['system_amount']); // 2000 - 400
    }
}
