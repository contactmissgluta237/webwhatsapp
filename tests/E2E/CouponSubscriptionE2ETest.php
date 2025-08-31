<?php

declare(strict_types=1);

namespace Tests\E2E;

use App\Enums\CouponStatus;
use App\Enums\CouponType;
use App\Models\Coupon;
use App\Models\Package;
use App\Models\ReferralEarning;
use App\Models\User;
use App\Models\UserSubscription;
use App\Models\Wallet;
use App\Services\CouponService;
use App\Services\Customer\PackageSubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CouponSubscriptionE2ETest extends TestCase
{
    use RefreshDatabase;

    private User $referrer;
    private User $customer;
    private User $admin;
    private Package $starterPackage;
    private Coupon $percentageCoupon;
    private PackageSubscriptionService $subscriptionService;
    private CouponService $couponService;

    protected function setUp(): void
    {
        parent::setUp();

        // Nettoyer l'état des événements traités avant chaque test
        \App\Listeners\BaseListener::clearProcessedEvents();

        // Create necessary roles
        \Spatie\Permission\Models\Role::create(['name' => 'admin']);
        \Spatie\Permission\Models\Role::create(['name' => 'customer']);

        // Seed packages - ensure we have the starter package
        $this->artisan('db:seed', ['--class' => 'PackagesSeeder']);
        $this->starterPackage = Package::where('name', 'starter')->first();

        // Create admin user
        $this->admin = User::factory()->admin()->create([
            'first_name' => 'Admin',
            'last_name' => 'System',
            'email' => 'admin@test.com',
        ]);

        // Create referrer (parrain) with wallet
        $this->referrer = User::factory()->customer()->create([
            'first_name' => 'Parrain',
            'last_name' => 'Référent',
            'email' => 'referrer@test.com',
            'affiliation_code' => 'PARRAIN123',
            'referral_commission_percentage' => 10.00, // 10% commission
        ]);

        $this->referrer->wallet()->create([
            'balance' => 0.00,
            'currency' => 'USD',
        ]);

        // Create customer (referred user) with 1000 USD wallet
        $this->customer = User::factory()->customer()->create([
            'first_name' => 'Client',
            'last_name' => 'Référé',
            'email' => 'customer@test.com',
            'referrer_id' => $this->referrer->id, // Set referrer relationship
        ]);

        $this->customer->wallet()->create([
            'balance' => 1500.00, // Suffisant pour 1000 USD (avec coupon) mais pas pour 2000 USD (sans coupon)
            'currency' => 'USD',
        ]);

        // Create 50% percentage coupon
        $this->percentageCoupon = Coupon::create([
            'code' => 'SAVE50',
            'type' => CouponType::PERCENTAGE()->value,
            'value' => 50.00,
            'status' => CouponStatus::ACTIVE()->value,
            'is_active' => true,
            'usage_limit' => 100,
            'per_user_limit' => 1,
            'used_count' => 0,
            'created_by' => $this->admin->id,
        ]);

        // Initialize services
        $this->subscriptionService = app(PackageSubscriptionService::class);
        $this->couponService = app(CouponService::class);
    }

    #[Test]
    public function test_complete_subscription_scenario_with_coupon_and_referral_system(): void
    {
        // ÉTAPE 1: Vérifier l'état initial
        $this->assertEquals(1500.00, $this->customer->wallet->balance);
        $this->assertEquals(0.00, $this->referrer->wallet->balance);
        $this->assertEquals(3000.00, $this->starterPackage->price);
        $this->assertEquals(2000.00, $this->starterPackage->promotional_price); // Prix promo actif
        $this->assertEquals(200, $this->starterPackage->messages_limit);

        // ÉTAPE 2: Première tentative sans coupon (doit échouer - solde insuffisant)
        try {
            $this->subscriptionService->subscribeDirectly(
                user: $this->customer,
                package: $this->starterPackage,
                couponCode: null
            );
            $this->fail('La souscription aurait dû échouer avec un solde insuffisant');
        } catch (\Exception $e) {
            $this->assertStringContainsString('Solde insuffisant', $e->getMessage());
            $this->assertStringContainsString('500', $e->getMessage()); // Amount missing (2000 - 1500)
        }

        // Vérifier qu'aucune transaction n'a eu lieu
        $this->assertEquals(1500.00, $this->customer->wallet->fresh()->balance);
        $this->assertEquals(0, UserSubscription::where('user_id', $this->customer->id)->count());

        // ÉTAPE 3: Deuxième tentative avec coupon 50% (doit réussir)
        $subscription1 = $this->subscriptionService->subscribeDirectly(
            user: $this->customer,
            package: $this->starterPackage,
            couponCode: 'SAVE50'
        );

        // Vérifier la souscription créée
        $this->assertNotNull($subscription1);
        $this->assertEquals($this->customer->id, $subscription1->user_id);
        $this->assertEquals($this->starterPackage->id, $subscription1->package_id);
        $this->assertEquals(1000.00, $subscription1->amount_paid); // 2000 (promo) - 50% = 1000
        $this->assertEquals(200, $subscription1->messages_limit);
        $this->assertEquals('active', $subscription1->status);

        // Vérifier que le portefeuille client a été débité
        $this->customer->wallet->refresh();
        $this->assertEquals(500.00, $this->customer->wallet->balance); // 1500 - 1000 = 500

        // Vérifier que le parrain a reçu sa commission (10% de 2000 = 200 USD sur prix original)
        $this->referrer->wallet->refresh();
        $this->assertEquals(100.00, $this->referrer->wallet->balance);

        // Vérifier l'enregistrement de l'earning du parrain
        $referralEarning = ReferralEarning::where('referrer_id', $this->referrer->id)
            ->where('referred_user_id', $this->customer->id)
            ->first();

        $this->assertNotNull($referralEarning);
        $this->assertEquals(100.00, $referralEarning->commission_amount);
        $this->assertEquals($subscription1->id, $referralEarning->user_subscription_id);

        // Vérifier que le coupon a été utilisé
        $this->percentageCoupon->refresh();
        $this->assertEquals(1, $this->percentageCoupon->used_count);

        $couponUsage = \DB::table('coupon_usages')
            ->where('coupon_id', $this->percentageCoupon->id)
            ->where('user_id', $this->customer->id)
            ->first();

        $this->assertNotNull($couponUsage);
        $this->assertEquals(2000.00, $couponUsage->original_price); // Prix promotionnel
        $this->assertEquals(1000.00, $couponUsage->discount_amount);
        $this->assertEquals(1000.00, $couponUsage->final_price);

        // ÉTAPE 4: Recharger le compte client de 2000 USD
        $this->customer->wallet->update(['balance' => 2000.00]);
        $this->assertEquals(2000.00, $this->customer->wallet->fresh()->balance);

        // ÉTAPE 5: Deuxième souscription au même forfait starter (sans coupon)
        $subscription2 = $this->subscriptionService->subscribeDirectly(
            user: $this->customer,
            package: $this->starterPackage,
            couponCode: null
        );

        // Vérifier la nouvelle souscription
        $this->assertNotNull($subscription2);
        $this->assertEquals(2000.00, $subscription2->amount_paid); // Prix promotionnel (pas de coupon)

        // Vérifier l'accumulation des messages: 200 (forfait) + messages restants du précédent
        $this->assertEquals(400, $subscription2->messages_limit); // 200 + 200 accumulés

        // Vérifier que le portefeuille client a été débité
        $this->customer->wallet->refresh();
        $this->assertEquals(0.00, $this->customer->wallet->balance); // 2000 - 2000 = 0

        // Vérifier que le parrain a reçu une nouvelle commission (10% de 2000 = 200 USD)
        $this->referrer->wallet->refresh();
        $this->assertEquals(300.00, $this->referrer->wallet->balance); // 100 + 200 = 300

        // Vérifier les earnings du parrain (2 au total)
        $referralEarnings = ReferralEarning::where('referrer_id', $this->referrer->id)->get();
        $this->assertEquals(2, $referralEarnings->count());
        $this->assertEquals(300.00, $referralEarnings->sum('commission_amount'));

        // ÉTAPE 6: Vérifier que l'admin voit les souscriptions dans la liste
        $response = $this->actingAs($this->admin)->get(route('admin.subscriptions.index'));
        $response->assertStatus(200);
        $response->assertSee($this->customer->full_name);
        $response->assertSee('Starter'); // Package name
        $response->assertSee('1,000 USD'); // Premier montant payé (format number_format)
        $response->assertSee('2,000 USD'); // Deuxième montant payé (format number_format)

        // Vérifier que l'admin voit le coupon utilisé dans la liste des coupons
        $response = $this->actingAs($this->admin)->get(route('admin.coupons.index'));
        $response->assertStatus(200);
        $response->assertSee('SAVE50');
        $response->assertSee('1/100'); // Usage count

        // Le coupon n'est pas expiré car il a encore des utilisations disponibles
        $this->percentageCoupon->refresh();
        $this->assertEquals(CouponStatus::ACTIVE()->value, $this->percentageCoupon->status->value);
        $this->assertTrue($this->percentageCoupon->is_active);

        // ÉTAPE 7: Vérifications finales sur les revenus du système
        // Le système devrait avoir reçu:
        // 1ère souscription: 1000 - 100 (commission) = 900 USD
        // 2ème souscription: 2000 - 200 (commission) = 1800 USD
        // Total système: 2700 USD

        $totalSystemRevenue = \DB::table('system_revenues')
            ->where('source_type', 'subscription')
            ->sum('amount');

        $this->assertEquals(2700.00, $totalSystemRevenue);

        // Vérifier le total des commissions de parrainage dans les transactions internes
        // Les commissions sont des crédits vers les portefeuilles des parrains
        $totalReferralCommissions = \DB::table('internal_transactions')
            ->where('transaction_type', 'credit')
            ->whereNotNull('recipient_user_id') // Transaction vers un utilisateur spécifique
            ->where('recipient_user_id', $this->referrer->id) // Transactions vers le parrain
            ->sum('amount');

        $this->assertEquals(300.00, $totalReferralCommissions);

        // ÉTAPE 8: Vérifier les détails des souscriptions pour l'admin
        $subscription1Response = $this->actingAs($this->admin)
            ->get(route('admin.subscriptions.index', ['user_id' => $this->customer->id]));

        $subscription1Response->assertStatus(200);
        $subscription1Response->assertSee('200'); // Messages limit première souscription
        $subscription1Response->assertSee('400'); // Messages limit deuxième souscription (accumulés)

        // Vérifier que les deux souscriptions sont bien dans la base
        $userSubscriptions = UserSubscription::where('user_id', $this->customer->id)
            ->orderBy('created_at')
            ->get();

        $this->assertEquals(2, $userSubscriptions->count());

        // Première souscription chronologique (avec coupon) - expirée
        $firstChronological = $userSubscriptions->where('amount_paid', 1000.00)->first();
        $this->assertNotNull($firstChronological);
        $this->assertEquals(1000.00, $firstChronological->amount_paid);
        $this->assertEquals('expired', $firstChronological->status); // Expirée après la deuxième

        // Deuxième souscription chronologique (sans coupon) - active
        $secondChronological = $userSubscriptions->where('amount_paid', 2000.00)->first();
        $this->assertNotNull($secondChronological);
        $this->assertEquals(400, $secondChronological->messages_limit); // 200 du package + 200 transférés de l'ancienne
        $this->assertEquals(2000.00, $secondChronological->amount_paid);
        $this->assertEquals('active', $secondChronological->status);
    }

    #[Test]
    public function test_coupon_expires_when_usage_limit_reached(): void
    {
        // Créer un coupon avec limite d'utilisation de 1
        $limitedCoupon = Coupon::create([
            'code' => 'LIMITED1',
            'type' => CouponType::PERCENTAGE()->value,
            'value' => 25.00,
            'status' => CouponStatus::ACTIVE()->value,
            'is_active' => true,
            'usage_limit' => 1,
            'per_user_limit' => 1,
            'used_count' => 0,
            'created_by' => $this->admin->id,
        ]);

        // Créer un autre client avec solde suffisant
        $customer2 = User::factory()->customer()->create([
            'first_name' => 'Client2',
            'last_name' => 'Test',
            'email' => 'customer2@test.com',
        ]);

        $customer2->wallet()->create([
            'balance' => 2000.00,
            'currency' => 'USD',
        ]);

        // Utiliser le coupon
        $subscription = $this->subscriptionService->subscribeDirectly(
            user: $customer2,
            package: $this->starterPackage,
            couponCode: 'LIMITED1'
        );

        $this->assertNotNull($subscription);

        // Vérifier que le coupon est maintenant expiré/utilisé
        $limitedCoupon->refresh();
        $this->assertEquals(1, $limitedCoupon->used_count);
        $this->assertEquals(CouponStatus::USED()->value, $limitedCoupon->status->value);
        $this->assertFalse($limitedCoupon->is_active);

        // Vérifier dans l'interface admin
        $response = $this->actingAs($this->admin)->get(route('admin.coupons.index'));
        $response->assertStatus(200);
        $response->assertSee('LIMITED1');
        $response->assertSee('1/1'); // Usage complet
        $response->assertSee('Utilisé'); // Statut utilisé
    }

    #[Test]
    public function test_admin_can_view_coupon_usage_details(): void
    {
        // Utiliser le coupon pour créer une souscription
        $this->customer->wallet->update(['balance' => 2000.00]);

        $subscription = $this->subscriptionService->subscribeDirectly(
            user: $this->customer,
            package: $this->starterPackage,
            couponCode: 'SAVE50'
        );

        // Vérifier que l'admin peut voir les détails d'utilisation
        $response = $this->actingAs($this->admin)->get(route('admin.coupons.index'));
        $response->assertStatus(200);

        // Vérifier les informations du coupon
        $response->assertSee('SAVE50');
        $response->assertSee('50%'); // Valeur du coupon
        $response->assertSee('1/100'); // Usage
        $response->assertSee($this->admin->full_name); // Créateur

        // Vérifier que le coupon apparaît dans les filtres
        $response->assertSee('Pourcentage'); // Type
        $response->assertSee('Actif'); // Statut
    }

    #[Test]
    public function test_referral_system_tracks_multiple_subscriptions(): void
    {
        // Nettoyer l'état des événements traités pour éviter les conflits
        \App\Listeners\BaseListener::clearProcessedEvents();

        // Réinitialiser le portefeuille du parrain pour ce test
        $this->referrer->wallet->update(['balance' => 0.00]);

        // Donner assez d'argent au client pour 2 souscriptions
        $this->customer->wallet->update(['balance' => 3000.00]);

        // Première souscription avec coupon
        $subscription1 = $this->subscriptionService->subscribeDirectly(
            user: $this->customer,
            package: $this->starterPackage,
            couponCode: 'SAVE50'
        );

        // Recharger pour la deuxième
        $this->customer->wallet->update(['balance' => $this->customer->wallet->balance + 2000.00]);

        // Deuxième souscription sans coupon
        $subscription2 = $this->subscriptionService->subscribeDirectly(
            user: $this->customer,
            package: $this->starterPackage,
            couponCode: null
        );

        // Vérifier les commissions du parrain
        $this->referrer->wallet->refresh();
        $expectedCommission = (1000 * 0.10) + (2000 * 0.10); // 100 + 200 = 300
        $this->assertEquals($expectedCommission, $this->referrer->wallet->balance);

        // Vérifier les enregistrements d'earnings
        $earnings = ReferralEarning::where('referrer_id', $this->referrer->id)->get();
        $this->assertEquals(2, $earnings->count());
        $this->assertEquals(100.00, $earnings->first()->commission_amount);
        $this->assertEquals(200.00, $earnings->last()->commission_amount);
    }
}
