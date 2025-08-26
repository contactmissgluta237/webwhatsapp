<?php

namespace Tests\Feature;

use App\Models\Package;
use App\Models\User;
use App\Models\UserSubscription;
use App\Models\WhatsAppAccount;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PackageSubscriptionCycleTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed les packages de base
        $this->seed(\Database\Seeders\PackagesSeeder::class);
    }

    #[Test]
    public function it_creates_account_usage_when_subscription_starts()
    {
        $user = User::factory()->create();
        $package = Package::findByName('business');
        $account = WhatsAppAccount::factory()->create(['user_id' => $user->id]);

        // Abonnement qui commence le 15 janvier
        Carbon::setTestNow('2025-01-15 10:00:00');

        $subscription = UserSubscription::create([
            'user_id' => $user->id,
            'package_id' => $package->id,
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
            'status' => 'active',
            'messages_limit' => $package->messages_limit,
            'context_limit' => $package->context_limit,
            'accounts_limit' => $package->accounts_limit,
            'products_limit' => $package->products_limit,
        ]);

        $accountUsage = $subscription->getUsageForAccount($account);

        // L'usage doit être initialisé à zéro
        $this->assertEquals(0, $accountUsage->messages_used);
        $this->assertEquals(0, $accountUsage->overage_messages_used);
        $this->assertEquals($subscription->id, $accountUsage->user_subscription_id);
        $this->assertEquals($account->id, $accountUsage->whats_app_account_id);
    }

    #[Test]
    public function it_tracks_usage_correctly_for_account()
    {
        $user = User::factory()->create();
        $package = Package::findByName('starter');
        $account = WhatsAppAccount::factory()->create(['user_id' => $user->id]);

        // Abonnement qui commence le 10 janvier
        $subscription = UserSubscription::create([
            'user_id' => $user->id,
            'package_id' => $package->id,
            'starts_at' => Carbon::parse('2025-01-10'),
            'ends_at' => Carbon::parse('2025-02-10'),
            'status' => 'active',
            'messages_limit' => $package->messages_limit,
            'context_limit' => $package->context_limit,
            'accounts_limit' => $package->accounts_limit,
            'products_limit' => $package->products_limit,
        ]);

        // Créer usage pour ce compte
        $accountUsage = $subscription->getUsageForAccount($account);

        // Test : on est le 25 janvier
        Carbon::setTestNow('2025-01-25 15:30:00');

        $currentUsage = $subscription->getUsageForAccount($account);
        $this->assertNotNull($currentUsage);
        $this->assertEquals($accountUsage->id, $currentUsage->id);
    }

    #[Test]
    public function it_tracks_subscription_expiration_correctly()
    {
        $user = User::factory()->create();
        $package = Package::findByName('starter');
        $account = WhatsAppAccount::factory()->create(['user_id' => $user->id]);

        // Abonnement qui commence le 10 janvier
        $subscription = UserSubscription::create([
            'user_id' => $user->id,
            'package_id' => $package->id,
            'starts_at' => Carbon::parse('2025-01-10'),
            'ends_at' => Carbon::parse('2025-02-10'),
            'status' => 'active',
            'messages_limit' => $package->messages_limit,
            'context_limit' => $package->context_limit,
            'accounts_limit' => $package->accounts_limit,
            'products_limit' => $package->products_limit,
        ]);

        // Créer usage pour ce compte (10 jan - 10 fév)
        $subscription->getUsageForAccount($account);

        // Test : on est le 15 février (après l'expiration)
        Carbon::setTestNow('2025-02-15 10:00:00');

        $this->assertTrue($subscription->isExpired());
        $this->assertFalse($subscription->isActive());
    }

    #[Test]
    public function it_calculates_usage_analytics_correctly_for_subscription()
    {
        $user = User::factory()->create();
        $package = Package::findByName('business');
        $account1 = WhatsAppAccount::factory()->create(['user_id' => $user->id]);
        $account2 = WhatsAppAccount::factory()->create(['user_id' => $user->id]);

        // Abonnement qui commence le 1er janvier
        $subscription = UserSubscription::create([
            'user_id' => $user->id,
            'package_id' => $package->id,
            'starts_at' => Carbon::parse('2025-01-01'),
            'ends_at' => Carbon::parse('2025-02-01'),
            'status' => 'active',
            'messages_limit' => $package->messages_limit,
            'context_limit' => $package->context_limit,
            'accounts_limit' => $package->accounts_limit,
            'products_limit' => $package->products_limit,
        ]);

        // Usage réparti sur plusieurs comptes
        $usage1 = $subscription->getUsageForAccount($account1);
        $usage2 = $subscription->getUsageForAccount($account2);

        $usage1->update(['messages_used' => 60, 'overage_messages_used' => 10]);
        $usage2->update(['messages_used' => 40, 'overage_messages_used' => 5]);

        // Test : on est le 10 janvier
        Carbon::setTestNow('2025-01-10 12:00:00');

        // Vérification des totaux
        $this->assertEquals(100, $subscription->getTotalMessagesUsed());
        $this->assertEquals(15, $subscription->getTotalOverageMessagesUsed());
        $this->assertEquals($package->messages_limit - 100, $subscription->getRemainingMessages());

        // Analytics par compte
        $this->assertEquals(70, $usage1->getTotalMessages()); // 60 + 10
        $this->assertEquals(45, $usage2->getTotalMessages()); // 40 + 5
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow(); // Reset Carbon
        parent::tearDown();
    }
}
