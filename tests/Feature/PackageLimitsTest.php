<?php

namespace Tests\Feature;

use App\Models\Package;
use App\Models\UserProduct;
use App\Models\User;
use App\Models\UserSubscription;
use App\Models\WhatsAppAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PackageLimitsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->seed(\Database\Seeders\PackagesSeeder::class);
    }

    #[Test]
    public function starter_package_cannot_have_products()
    {
        $user = User::factory()->create();
        $starterPackage = Package::findByName('starter');
        
        $subscription = UserSubscription::create([
            'user_id' => $user->id,
            'package_id' => $starterPackage->id,
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
            'status' => 'active',
        ]);

        // Vérifier que la limite de produits est bien 0
        $this->assertEquals(0, $starterPackage->products_limit);
        
        // Le package starter ne devrait pas permettre de produits
        $this->assertFalse($user->canCreateProduct());
    }

    #[Test]
    public function business_package_allows_unlimited_products()
    {
        $user = User::factory()->create();
        $businessPackage = Package::findByName('business');
        
        UserSubscription::create([
            'user_id' => $user->id,
            'package_id' => $businessPackage->id,
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
            'status' => 'active',
        ]);

        // Vérifier que la limite de produits est élevée ou illimitée
        $this->assertGreaterThan(0, $businessPackage->products_limit);
        
        // Créer des produits
        $this->assertTrue($user->canCreateProduct());
    }

    #[Test]
    public function starter_package_blocks_messages_after_limit()
    {
        $user = User::factory()->create();
        $starterPackage = Package::findByName('starter');
        
        $subscription = UserSubscription::create([
            'user_id' => $user->id,
            'package_id' => $starterPackage->id,
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
            'status' => 'active',
        ]);

        $tracker = $subscription->getOrCreateCurrentCycleTracker();
        
        // Vérifier la limite initiale (200 messages pour starter)
        $this->assertEquals(200, $starterPackage->messages_limit);
        $this->assertEquals(200, $tracker->messages_remaining);
        $this->assertTrue($tracker->hasRemainingMessages());
        
        // Utiliser tous les messages
        $tracker->incrementUsage(200);
        
        // Vérifier qu'il n'y a plus de messages disponibles
        $this->assertEquals(0, $tracker->messages_remaining);
        $this->assertFalse($tracker->hasRemainingMessages());
        
        // Vérifier que l'utilisateur ne peut plus envoyer de messages
        $this->assertFalse($user->hasRemainingMessages());
    }

    #[Test]
    public function business_package_has_higher_message_limit()
    {
        $user = User::factory()->create();
        $businessPackage = Package::findByName('business');
        
        $subscription = UserSubscription::create([
            'user_id' => $user->id,
            'package_id' => $businessPackage->id,
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
            'status' => 'active',
        ]);

        $tracker = $subscription->getOrCreateCurrentCycleTracker();
        
        // Vérifier que business a plus de messages que starter
        $starterPackage = Package::findByName('starter');
        $this->assertGreaterThan($starterPackage->messages_limit, $businessPackage->messages_limit);
        $this->assertEquals($businessPackage->messages_limit, $tracker->messages_remaining);
    }

    #[Test]
    public function starter_package_allows_only_one_whatsapp_account()
    {
        $user = User::factory()->create();
        $starterPackage = Package::findByName('starter');
        
        UserSubscription::create([
            'user_id' => $user->id,
            'package_id' => $starterPackage->id,
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
            'status' => 'active',
        ]);

        // Vérifier la limite d'accounts
        $this->assertEquals(1, $starterPackage->accounts_limit);
        
        // Créer un premier compte WhatsApp
        $account1 = WhatsAppAccount::factory()->create(['user_id' => $user->id]);
        $this->assertTrue($user->canLinkWhatsAppAccount());
        
        // Essayer de créer un deuxième compte (devrait être bloqué)
        $user->refresh();
        $this->assertFalse($user->canLinkWhatsAppAccount());
    }

    #[Test]
    public function trial_package_has_strict_limits()
    {
        $user = User::factory()->create();
        $trialPackage = Package::findByName('trial');
        
        $subscription = UserSubscription::create([
            'user_id' => $user->id,
            'package_id' => $trialPackage->id,
            'starts_at' => now(),
            'ends_at' => now()->addDays(7),
            'status' => 'active',
        ]);

        // Vérifier les limites du trial
        $this->assertEquals(50, $trialPackage->messages_limit);
        $this->assertEquals(0, $trialPackage->products_limit);
        $this->assertEquals(1, $trialPackage->accounts_limit);
        $this->assertTrue($trialPackage->one_time_only);
        
        // Vérifier qu'on ne peut pas souscrire deux fois au trial
        $this->assertFalse($user->hasTrialAvailable());
    }

    #[Test]
    public function user_without_subscription_has_no_limits()
    {
        $user = User::factory()->create();
        
        // Utilisateur sans abonnement actif
        $this->assertFalse($user->hasActiveSubscription());
        $this->assertEquals(0, $user->getRemainingMessages());
        $this->assertFalse($user->hasRemainingMessages());
    }
}
