<?php

namespace Tests\Feature;

use App\Models\Package;
use App\Models\User;
use App\Models\UserProduct;
use App\Models\UserSubscription;
use App\Models\WhatsAppAccount;
use App\Models\WhatsAppAccountUsage;
use App\Services\WhatsApp\Helpers\MessageCostHelper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class PackageSubscriptionSystemTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed les packages de base
        $this->seed(\Database\Seeders\PackagesSeeder::class);
    }

    /** @test */
    public function it_can_create_packages_with_correct_attributes()
    {
        $packages = Package::all();

        $this->assertCount(4, $packages);

        $trial = Package::findByName('trial');
        $this->assertTrue($trial->isTrial());
        $this->assertTrue($trial->one_time_only);
        $this->assertEquals(7, $trial->duration_days);
        $this->assertEquals(0, $trial->price);

        $starter = Package::findByName('starter');
        $this->assertTrue($starter->isStarter());
        $this->assertFalse($starter->allowsProducts());
        $this->assertEquals(2000, $starter->price);

        $business = Package::findByName('business');
        $this->assertTrue($business->isBusiness());
        $this->assertTrue($business->allowsProducts());
        $this->assertEquals(5, $business->products_limit);

        $pro = Package::findByName('pro');
        $this->assertTrue($pro->isPro());
        $this->assertTrue($pro->hasWeeklyReports());
        $this->assertTrue($pro->hasPrioritySupport());
    }

    /** @test */
    public function it_can_create_user_subscription()
    {
        $user = User::factory()->create();
        $package = Package::findByName('starter');

        $subscription = UserSubscription::create([
            'user_id' => $user->id,
            'package_id' => $package->id,
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
            'status' => 'active',
        ]);

        $this->assertInstanceOf(UserSubscription::class, $subscription);
        $this->assertTrue($subscription->isActive());
        $this->assertFalse($subscription->isExpired());
        $this->assertEquals($package->id, $subscription->package_id);
        $this->assertEquals($user->id, $subscription->user_id);
    }

    /** @test */
    public function it_can_track_user_active_subscription()
    {
        $user = User::factory()->create();
        $package = Package::findByName('business');

        // Créer un abonnement actif
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

        $user->refresh();

        $this->assertTrue($user->hasActiveSubscription());
        $this->assertEquals($subscription->id, $user->activeSubscription->id);
        $this->assertEquals($package->id, $user->getCurrentPackage()->id);
    }

    /** @test */
    public function it_can_create_and_manage_account_usage()
    {
        $user = User::factory()->create();
        $package = Package::findByName('business');
        $account = WhatsAppAccount::factory()->create(['user_id' => $user->id]);

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

        $this->assertInstanceOf(WhatsAppAccountUsage::class, $accountUsage);
        $this->assertEquals($package->messages_limit, $subscription->getRemainingMessages());
        $this->assertEquals(0, $accountUsage->messages_used);

        // Test d'increment de l'usage
        $accountUsage->incrementUsage(5);

        $this->assertEquals(5, $accountUsage->messages_used);
        $this->assertEquals($package->messages_limit - 5, $subscription->getRemainingMessages());
        $this->assertTrue($subscription->hasRemainingMessages());
    }

    /** @test */
    public function it_can_calculate_message_costs_correctly()
    {
        // Créer quelques produits avec des médias
        $products = collect([
            $this->createMockUserProduct(2), // 2 médias
            $this->createMockUserProduct(3), // 3 médias
            $this->createMockUserProduct(1), // 1 média
        ]);

        $cost = MessageCostHelper::calculateMessageCost($products);

        // 1 message de base + 6 médias = 7 total
        $this->assertEquals(7, $cost);

        $detailedCost = MessageCostHelper::calculateDetailedCost($products);

        $this->assertEquals(1, $detailedCost['base_messages']);
        $this->assertEquals(6, $detailedCost['media_messages']);
        $this->assertEquals(7, $detailedCost['total_cost']);
        $this->assertEquals(3, $detailedCost['products_count']);

        $expectedXAF = 7 * config('pricing.message_base_cost_xaf', 10);
        $this->assertEquals($expectedXAF, $detailedCost['estimated_xaf']);
    }

    /** @test */
    public function it_can_validate_trial_subscription_rules()
    {
        $user = User::factory()->create();
        $trialPackage = Package::findByName('trial');

        // Créer un abonnement trial
        $subscription = UserSubscription::create([
            'user_id' => $user->id,
            'package_id' => $trialPackage->id,
            'starts_at' => now(),
            'ends_at' => now()->addDays(7),
            'status' => 'active',
            'messages_limit' => $trialPackage->messages_limit,
            'context_limit' => $trialPackage->context_limit,
            'accounts_limit' => $trialPackage->accounts_limit,
            'products_limit' => $trialPackage->products_limit,
        ]);

        $this->assertTrue($subscription->isTrialSubscription());

        // L'utilisateur ne peut plus avoir un autre trial
        $this->assertFalse($subscription->canSubscribeToTrial());

        // Test avec un nouvel abonnement non-trial
        $starterPackage = Package::findByName('starter');
        $starterSubscription = UserSubscription::create([
            'user_id' => $user->id,
            'package_id' => $starterPackage->id,
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
            'status' => 'active',
            'messages_limit' => $starterPackage->messages_limit,
            'context_limit' => $starterPackage->context_limit,
            'accounts_limit' => $starterPackage->accounts_limit,
            'products_limit' => $starterPackage->products_limit,
        ]);

        // Même avec un abonnement starter, l'utilisateur ne peut plus jamais avoir de trial
        // car il a déjà utilisé son trial une seule fois autorisé
        $this->assertFalse($starterSubscription->canSubscribeToTrial());
    }

    /** @test */
    public function it_can_check_package_features_correctly()
    {
        $trial = Package::findByName('trial');
        $this->assertFalse($trial->allowsProducts());
        $this->assertFalse($trial->allowsMultipleAccounts());
        $this->assertFalse($trial->hasWeeklyReports());

        $business = Package::findByName('business');
        $this->assertTrue($business->allowsProducts());
        $this->assertTrue($business->allowsMultipleAccounts());
        $this->assertFalse($business->hasWeeklyReports());

        $pro = Package::findByName('pro');
        $this->assertTrue($pro->allowsProducts());
        $this->assertTrue($pro->allowsMultipleAccounts());
        $this->assertTrue($pro->hasWeeklyReports());
        $this->assertTrue($pro->hasPrioritySupport());
    }

    /** @test */
    public function it_can_track_usage_across_multiple_accounts()
    {
        $user = User::factory()->create();
        $package = Package::findByName('business');
        $account1 = WhatsAppAccount::factory()->create(['user_id' => $user->id]);
        $account2 = WhatsAppAccount::factory()->create(['user_id' => $user->id]);

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

        $usage1 = $subscription->getUsageForAccount($account1);
        $usage2 = $subscription->getUsageForAccount($account2);

        // Utiliser quelques messages sur chaque compte
        $usage1->incrementUsage(60);
        $usage2->incrementUsage(40);

        $this->assertEquals(60, $usage1->messages_used);
        $this->assertEquals(40, $usage2->messages_used);

        // Vérification des totaux au niveau subscription
        $this->assertEquals(100, $subscription->getTotalMessagesUsed());
        $this->assertEquals($package->messages_limit - 100, $subscription->getRemainingMessages());

        // Chaque usage est distinct
        $this->assertNotEquals($usage1->id, $usage2->id);
        $this->assertEquals($account1->id, $usage1->whats_app_account_id);
        $this->assertEquals($account2->id, $usage2->whats_app_account_id);
    }

    /**
     * Helper pour créer un mock UserProduct avec un nombre spécifique de médias
     */
    private function createMockUserProduct(int $mediaCount): object
    {
        $product = new class
        {
            public $mediaCount;

            public function getMediaCollection(string $collection)
            {
                return new class($this->mediaCount)
                {
                    public $count;

                    public function __construct($count)
                    {
                        $this->count = $count;
                    }

                    public function count()
                    {
                        return $this->count;
                    }
                };
            }
        };

        $product->mediaCount = $mediaCount;

        return $product;
    }
}
