<?php

declare(strict_types=1);

namespace Tests\Feature\Customer;

use App\Enums\PermissionEnum;
use App\Enums\UserRole;
use App\Models\Package;
use App\Models\User;
use App\Models\UserSubscription;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_customer_can_view_packages_page(): void
    {
        $response = $this->actingAs($this->user)->get(route('customer.packages.index'));

        $response->assertStatus(200);
        $response->assertViewIs('customer.packages.index');
        $response->assertViewHas('packages');
        $response->assertSee('Packages disponibles');
    }

    public function test_packages_are_displayed_with_correct_information(): void
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

    public function test_customer_with_no_wallet_cannot_subscribe_to_paid_package(): void
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

    public function test_customer_with_insufficient_wallet_cannot_subscribe(): void
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

    public function test_customer_can_subscribe_to_trial_package(): void
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

    public function test_customer_can_subscribe_to_paid_package_with_sufficient_wallet(): void
    {
        // Créer un wallet avec un solde suffisant
        $wallet = Wallet::factory()->create([
            'user_id' => $this->user->id,
            'balance' => 5000,
        ]);

        $package = Package::where('name', 'starter')->first();

        $response = $this->actingAs($this->user)
            ->post(route('customer.packages.subscribe', $package->id));

        $response->assertRedirect(route('customer.packages.index'));
        $response->assertSessionHas('success');

        // Vérifier que l'abonnement a été créé
        $this->assertDatabaseHas('user_subscriptions', [
            'user_id' => $this->user->id,
            'package_id' => $package->id,
            'status' => 'active',
            'amount_paid' => $package->price,
            'payment_method' => 'wallet',
        ]);

        // Vérifier que le wallet a été débité
        $wallet->refresh();
        $this->assertEquals(3000, $wallet->balance); // 5000 - 2000

        // Vérifier qu'une transaction a été créée
        $this->assertDatabaseHas('internal_transactions', [
            'wallet_id' => $wallet->id,
            'amount' => $package->price,
            'transaction_type' => 'debit',
            'description' => "Souscription au package {$package->display_name}",
        ]);
    }

    public function test_customer_cannot_subscribe_twice_to_trial(): void
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

    public function test_customer_cannot_subscribe_when_has_active_subscription(): void
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
        $response->assertSessionHasErrorsIn('error', 'Vous avez déjà un abonnement actif');
    }

    public function test_packages_page_shows_current_subscription_info(): void
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
        $response->assertSee('Abonnement actuel');
        $response->assertSee($package->display_name);
        $response->assertSee('messages restants');
    }

    public function test_subscription_buttons_are_correctly_displayed(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('customer.packages.index'));

        // Sans abonnement actif, tous les boutons "Souscrire" doivent être visibles
        $response->assertSee('Souscrire', false); // Pour les packages payants
        $response->assertSee('Commencer l\'essai', false); // Pour le trial

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

        // Avec un abonnement actif, le bouton du package actuel doit être "Abonnement actuel"
        $response->assertSee('Abonnement actuel');
        $response->assertSee('Abonnement en cours'); // Pour les autres packages
    }
}
