<?php

namespace Tests\Feature;

use App\Models\Package;
use App\Models\User;
use App\Models\UserProduct;
use App\Models\UserSubscription;
use App\Models\UsageSubscriptionTracker;
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
        ]);
        
        $user->refresh();
        
        $this->assertTrue($user->hasActiveSubscription());
        $this->assertEquals($subscription->id, $user->activeSubscription->id);
        $this->assertEquals($package->id, $user->getCurrentPackage()->id);
    }

    /** @test */
    public function it_can_create_and_manage_usage_trackers()
    {
        $user = User::factory()->create();
        $package = Package::findByName('business');
        
        $subscription = UserSubscription::create([
            'user_id' => $user->id,
            'package_id' => $package->id,
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
            'status' => 'active',
        ]);
        
        $tracker = $subscription->getOrCreateCurrentCycleTracker();
        
        $this->assertInstanceOf(UsageSubscriptionTracker::class, $tracker);
        $this->assertEquals($package->messages_limit, $tracker->messages_remaining);
        $this->assertEquals(0, $tracker->messages_used);
        
        // Test d'increment de l'usage
        $tracker->incrementUsage(5);
        
        $this->assertEquals(5, $tracker->messages_used);
        $this->assertEquals($package->messages_limit - 5, $tracker->messages_remaining);
        $this->assertTrue($tracker->hasRemainingMessages());
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
    public function it_can_reset_usage_for_new_cycle()
    {
        $user = User::factory()->create();
        $package = Package::findByName('business');
        
        $subscription = UserSubscription::create([
            'user_id' => $user->id,
            'package_id' => $package->id,
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
            'status' => 'active',
        ]);
        
        $tracker = $subscription->getOrCreateCurrentCycleTracker();
        
        // Utiliser quelques messages
        $tracker->incrementUsage(100);
        
        $this->assertEquals(100, $tracker->messages_used);
        $this->assertEquals($package->messages_limit - 100, $tracker->messages_remaining);
        
        // Reset pour le nouveau cycle
        $tracker->resetForNewCycle();
        
        $this->assertEquals(0, $tracker->messages_used);
        $this->assertEquals($package->messages_limit, $tracker->messages_remaining);
        $this->assertNotNull($tracker->last_reset_at);
    }

    /**
     * Helper pour créer un mock UserProduct avec un nombre spécifique de médias
     */
    private function createMockUserProduct(int $mediaCount): object
    {
        $product = new class {
            public $mediaCount;
            
            public function getMediaCollection(string $collection) 
            {
                return new class($this->mediaCount) {
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