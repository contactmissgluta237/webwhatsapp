<?php

declare(strict_types=1);

namespace Tests\Feature\Customer;

use App\Enums\CouponType;
use App\Enums\PermissionEnum;
use App\Enums\UserRole;
use App\Models\Coupon;
use App\Models\Package;
use App\Models\User;
use App\Models\UserSubscription;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PackageManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $permissions = PermissionEnum::values();
        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        $customerRole = Role::create(['name' => 'customer']);
        $customerRole->givePermissionTo(UserRole::CUSTOMER()->permissions());

        // Seed packages
        $this->artisan('db:seed', ['--class' => 'PackagesSeeder']);

        $this->user = User::factory()->create();
        $this->user->assignRole('customer');
    }

    #[Test]
    public function customer_can_view_packages_page(): void
    {
        $response = $this->actingAs($this->user)->get(route('customer.packages.index'));

        $response->assertStatus(200);
        $response->assertViewIs('customer.packages.index');
        $response->assertViewHas('packages');
        $response->assertSee('Available Packages');
    }

    #[Test]
    public function packages_are_displayed_with_correct_information(): void
    {
        $response = $this->actingAs($this->user)->get(route('customer.packages.index'));

        $response->assertStatus(200);

        // Vérifier que les packages principaux sont affichés
        $response->assertSee('Essai Gratuit'); // Trial
        $response->assertSee('Starter');
        $response->assertSee('Pro');
        $response->assertSee('Business');

        // Vérifier que les prix sont affichés (au moins quelques-uns)
        $response->assertSee('GRATUIT'); // Prix du trial
        $response->assertSee('2000'); // Prix starter ou similar
    }

    #[Test]
    public function customer_with_no_wallet_cannot_subscribe_to_paid_package(): void
    {
        $package = Package::where('name', 'starter')->first();

        $response = $this->actingAs($this->user)
            ->post(route('customer.packages.subscribe', $package->id));

        $response->assertRedirect(route('customer.packages.index'));
        $response->assertSessionHas('error');
        $response->assertSessionHas('recharge_needed', true);

        // Vérifier qu'aucun abonnement n'a été créé
        $this->assertDatabaseMissing('user_subscriptions', [
            'user_id' => $this->user->id,
            'package_id' => $package->id,
        ]);
    }

    #[Test]
    public function customer_with_insufficient_wallet_cannot_subscribe(): void
    {
        // Créer un wallet avec un solde insuffisant
        Wallet::factory()->create([
            'user_id' => $this->user->id,
            'balance' => 1000, // Insuffisant pour le starter (2000)
        ]);

        $package = Package::where('name', 'starter')->first();

        $response = $this->actingAs($this->user)
            ->post(route('customer.packages.subscribe', $package->id));

        $response->assertRedirect(route('customer.packages.index'));
        $response->assertSessionHas('error');
        $response->assertSessionHas('missing_amount', 1000);

        $this->assertDatabaseMissing('user_subscriptions', [
            'user_id' => $this->user->id,
            'package_id' => $package->id,
        ]);
    }

    #[Test]
    public function customer_can_subscribe_to_trial_package(): void
    {
        $package = Package::where('name', 'trial')->first();

        $response = $this->actingAs($this->user)
            ->post(route('customer.packages.subscribe', $package->id));

        $response->assertRedirect(route('customer.packages.index'));
        $response->assertSessionHas('success');

        // Vérifier que l'abonnement a été créé
        $this->assertDatabaseHas('user_subscriptions', [
            'user_id' => $this->user->id,
            'package_id' => $package->id,
            'status' => 'active',
            'amount_paid' => 0,
            'payment_method' => 'wallet',
        ]);
    }

    #[Test]
    public function customer_can_subscribe_to_paid_package_with_sufficient_wallet(): void
    {
        // Créer un wallet avec un solde suffisant
        $wallet = Wallet::factory()->create([
            'user_id' => $this->user->id,
            'balance' => 5000,
        ]);

        $package = Package::where('name', 'starter')->first();
        $currentPrice = $package->getCurrentPrice(); // Utiliser le prix actuel (avec promotions)

        $response = $this->actingAs($this->user)
            ->post(route('customer.packages.subscribe', $package->id));

        $response->assertRedirect(route('customer.packages.index'));
        $response->assertSessionHas('success');

        // Vérifier que l'abonnement a été créé
        $this->assertDatabaseHas('user_subscriptions', [
            'user_id' => $this->user->id,
            'package_id' => $package->id,
            'status' => 'active',
            'amount_paid' => $currentPrice,
            'payment_method' => 'wallet',
        ]);

        // Vérifier que le wallet a été débité du bon montant
        $wallet->refresh();
        $this->assertEquals(5000 - $currentPrice, $wallet->balance);

        // Vérifier qu'une transaction a été créée (peut inclure info promotion)
        $this->assertDatabaseHas('internal_transactions', [
            'wallet_id' => $wallet->id,
            'amount' => $currentPrice,
            'transaction_type' => 'debit',
        ]);

        // Vérifier que la description contient au moins le nom du package
        $transaction = \Illuminate\Support\Facades\DB::table('internal_transactions')
            ->where('wallet_id', $wallet->id)
            ->where('transaction_type', 'debit')
            ->first();
        $this->assertStringContainsString($package->display_name, $transaction->description);
    }

    #[Test]
    public function customer_cannot_subscribe_twice_to_trial(): void
    {
        $package = Package::where('name', 'trial')->first();

        // Première souscription
        $this->actingAs($this->user)
            ->post(route('customer.packages.subscribe', $package->id));

        // Créer un autre abonnement trial manuellement dans le passé
        UserSubscription::create([
            'user_id' => $this->user->id,
            'package_id' => $package->id,
            'starts_at' => now()->subDays(10),
            'ends_at' => now()->subDays(3),
            'status' => 'expired',
            'messages_limit' => $package->messages_limit,
            'context_limit' => $package->context_limit,
            'accounts_limit' => $package->accounts_limit,
            'products_limit' => $package->products_limit,
        ]);

        // Deuxième tentative
        $response = $this->actingAs($this->user)
            ->post(route('customer.packages.subscribe', $package->id));

        $response->assertRedirect(route('customer.packages.index'));
        $response->assertSessionHas('error', 'Vous avez déjà utilisé votre essai gratuit.');
    }

    #[Test]
    public function customer_cannot_subscribe_when_has_active_subscription(): void
    {
        // Créer un abonnement actif
        $activePackage = Package::where('name', 'starter')->first();
        UserSubscription::create([
            'user_id' => $this->user->id,
            'package_id' => $activePackage->id,
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
            'status' => 'active',
            'messages_limit' => $activePackage->messages_limit,
            'context_limit' => $activePackage->context_limit,
            'accounts_limit' => $activePackage->accounts_limit,
            'products_limit' => $activePackage->products_limit,
        ]);

        // Créer un wallet
        Wallet::factory()->create([
            'user_id' => $this->user->id,
            'balance' => 10000,
        ]);

        // Tenter de s'abonner à un autre package
        $newPackage = Package::where('name', 'business')->first();
        $response = $this->actingAs($this->user)
            ->post(route('customer.packages.subscribe', $newPackage->id));

        $response->assertRedirect(route('customer.packages.index'));
        $response->assertSessionHas('error');
        // Note: Le message exact peut être différent selon la logique métier
    }

    #[Test]
    public function packages_page_shows_current_subscription_info(): void
    {
        // Créer un abonnement actif
        $package = Package::where('name', 'starter')->first();
        UserSubscription::create([
            'user_id' => $this->user->id,
            'package_id' => $package->id,
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
            'status' => 'active',
            'messages_limit' => $package->messages_limit,
            'context_limit' => $package->context_limit,
            'accounts_limit' => $package->accounts_limit,
            'products_limit' => $package->products_limit,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('customer.packages.index'));

        $response->assertStatus(200);
        $response->assertSee('Current subscription:');
        $response->assertSee($package->display_name);
        $response->assertSee('messages restants');
    }

    #[Test]
    public function subscription_buttons_are_correctly_displayed(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('customer.packages.index'));

        // Sans abonnement actif, tous les boutons "Souscrire" doivent être visibles
        $response->assertSee('Souscrire', false); // Pour les packages payants
        $response->assertSee('Commencer l\'essai', false); // Pour le trial

        // Créer un abonnement actif
        $package = Package::where('name', 'starter')->first();
        $subscription = UserSubscription::create([
            'user_id' => $this->user->id,
            'package_id' => $package->id,
            'starts_at' => now()->subMinute(), // S'assurer que c'est dans le passé
            'ends_at' => now()->addMonth(),    // S'assurer que c'est dans le futur
            'status' => 'active',
            'messages_limit' => $package->messages_limit,
            'context_limit' => $package->context_limit,
            'accounts_limit' => $package->accounts_limit,
            'products_limit' => $package->products_limit,
        ]);

        // Vérifier que l'abonnement a été créé et est actif
        $this->assertNotNull($subscription);
        $this->user->refresh();
        $this->assertTrue($this->user->hasActiveSubscription());

        $response = $this->actingAs($this->user)
            ->get(route('customer.packages.index'));

        // Vérifier d'abord que l'abonnement actuel est bien affiché
        $response->assertSee('Current subscription:'); // Dans l'alerte en haut de page

        // Puis vérifier que le bouton "En cours" est affiché
        // Note: Le texte peut être sensible à la casse
        $response->assertSee('En cours');
    }

    #[Test]
    public function customer_can_subscribe_with_valid_coupon(): void
    {
        // Créer un wallet avec un solde suffisant
        $wallet = Wallet::factory()->create([
            'user_id' => $this->user->id,
            'balance' => 5000,
        ]);

        $package = Package::where('name', 'starter')->first();

        // Créer un coupon de réduction de 20%
        $coupon = new Coupon([
            'code' => 'DISCOUNT20',
            'type' => CouponType::PERCENTAGE(),
            'value' => 20.0,
            'status' => \App\Enums\CouponStatus::ACTIVE(),
            'is_active' => true,
            'usage_limit' => 100,
            'per_user_limit' => 1,
            'used_count' => 0,
            'valid_from' => now()->subDay(),
            'valid_until' => now()->addMonth(),
            'created_by' => $this->user->id,
        ]);
        $coupon->save();

        $originalPrice = $package->getCurrentPrice();
        $expectedFinalPrice = $originalPrice * 0.8; // 20% de réduction

        $response = $this->actingAs($this->user)
            ->post(route('customer.packages.subscribe', $package->id), [
                'coupon_code' => 'DISCOUNT20',
            ]);

        $response->assertRedirect(route('customer.packages.index'));
        $response->assertSessionHas('success');

        // Vérifier que l'abonnement a été créé avec le prix réduit
        $this->assertDatabaseHas('user_subscriptions', [
            'user_id' => $this->user->id,
            'package_id' => $package->id,
            'status' => 'active',
            'amount_paid' => $expectedFinalPrice,
            'payment_method' => 'wallet',
        ]);

        // Vérifier que le coupon a été utilisé
        $this->assertDatabaseHas('coupon_usages', [
            'coupon_id' => $coupon->id,
            'user_id' => $this->user->id,
            'original_price' => $originalPrice,
            'discount_amount' => $originalPrice - $expectedFinalPrice,
            'final_price' => $expectedFinalPrice,
        ]);

        // Vérifier que le wallet a été débité du bon montant
        $wallet->refresh();
        $this->assertEquals(5000 - $expectedFinalPrice, $wallet->balance);
    }

    #[Test]
    public function customer_cannot_subscribe_with_invalid_coupon(): void
    {
        // Créer un wallet avec un solde suffisant
        $wallet = Wallet::factory()->create([
            'user_id' => $this->user->id,
            'balance' => 5000,
        ]);

        $package = Package::where('name', 'starter')->first();

        $response = $this->actingAs($this->user)
            ->post(route('customer.packages.subscribe', $package->id), [
                'coupon_code' => 'INVALID_CODE',
            ]);

        $response->assertRedirect(route('customer.packages.index'));
        $response->assertSessionHas('error');

        // Vérifier qu'aucun abonnement n'a été créé
        $this->assertDatabaseMissing('user_subscriptions', [
            'user_id' => $this->user->id,
            'package_id' => $package->id,
        ]);

        // Vérifier que le wallet n'a pas été débité
        $wallet->refresh();
        $this->assertEquals(5000, $wallet->balance);
    }

    #[Test]
    public function customer_benefits_from_promotional_pricing(): void
    {
        // Créer un wallet avec un solde suffisant
        $wallet = Wallet::factory()->create([
            'user_id' => $this->user->id,
            'balance' => 5000,
        ]);

        $package = Package::where('name', 'starter')->first();

        // Appliquer une promotion active
        $package->update([
            'promotional_price' => $package->price * 0.5, // 50% de réduction
            'promotion_starts_at' => now()->subDay(),
            'promotion_ends_at' => now()->addWeek(),
            'promotion_is_active' => true,
        ]);

        $promotionalPrice = $package->getCurrentPrice();
        $this->assertTrue($package->hasActivePromotion());
        $this->assertEquals($package->promotional_price, $promotionalPrice);

        $response = $this->actingAs($this->user)
            ->post(route('customer.packages.subscribe', $package->id));

        $response->assertRedirect(route('customer.packages.index'));
        $response->assertSessionHas('success');

        // Vérifier que l'abonnement a été créé avec le prix promotionnel
        $this->assertDatabaseHas('user_subscriptions', [
            'user_id' => $this->user->id,
            'package_id' => $package->id,
            'status' => 'active',
            'amount_paid' => $promotionalPrice,
            'payment_method' => 'wallet',
        ]);

        // Vérifier que le wallet a été débité du prix promotionnel
        $wallet->refresh();
        $this->assertEquals(5000 - $promotionalPrice, $wallet->balance);
    }

    #[Test]
    public function customer_can_apply_coupon_on_promotional_price(): void
    {
        // Créer un wallet avec un solde suffisant
        $wallet = Wallet::factory()->create([
            'user_id' => $this->user->id,
            'balance' => 5000,
        ]);

        $package = Package::where('name', 'starter')->first();

        // Appliquer une promotion active
        $package->update([
            'promotional_price' => $package->price * 0.8, // 20% de réduction de base
            'promotion_starts_at' => now()->subDay(),
            'promotion_ends_at' => now()->addWeek(),
            'promotion_is_active' => true,
        ]);

        // Créer un coupon de réduction fixe
        $coupon = new Coupon([
            'code' => 'SAVE500',
            'type' => CouponType::FIXED_AMOUNT(),
            'value' => 500.0,
            'status' => \App\Enums\CouponStatus::ACTIVE(),
            'is_active' => true,
            'usage_limit' => 100,
            'per_user_limit' => 1,
            'used_count' => 0,
            'valid_from' => now()->subDay(),
            'valid_until' => now()->addMonth(),
            'created_by' => $this->user->id,
        ]);
        $coupon->save();

        $promotionalPrice = $package->getCurrentPrice(); // Prix promotionnel
        $expectedFinalPrice = $promotionalPrice - 500; // Application du coupon sur le prix promotionnel

        $response = $this->actingAs($this->user)
            ->post(route('customer.packages.subscribe', $package->id), [
                'coupon_code' => 'SAVE500',
            ]);

        $response->assertRedirect(route('customer.packages.index'));
        $response->assertSessionHas('success');

        // Vérifier que l'abonnement a été créé avec le prix final (promotion + coupon)
        $this->assertDatabaseHas('user_subscriptions', [
            'user_id' => $this->user->id,
            'package_id' => $package->id,
            'status' => 'active',
            'amount_paid' => $expectedFinalPrice,
            'payment_method' => 'wallet',
        ]);

        // Vérifier que le coupon a été appliqué sur le prix promotionnel
        $this->assertDatabaseHas('coupon_usages', [
            'coupon_id' => $coupon->id,
            'user_id' => $this->user->id,
            'original_price' => $promotionalPrice,
            'discount_amount' => 500,
            'final_price' => $expectedFinalPrice,
        ]);
    }
}
