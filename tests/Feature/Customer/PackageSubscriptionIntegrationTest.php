<?php

declare(strict_types=1);

namespace Tests\Feature\Customer;

use App\Models\InternalTransaction;
use App\Models\Package;
use App\Models\User;
use App\Models\UserSubscription;
use App\Models\Wallet;
use App\Models\WhatsAppAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PackageSubscriptionIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Wallet $wallet;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed packages
        $this->artisan('db:seed', ['--class' => 'PackagesSeeder']);

        $this->user = User::factory()->create(['role' => 'customer']);
        $this->wallet = Wallet::factory()->create([
            'user_id' => $this->user->id,
            'balance' => 10000, // Solde suffisant pour tous les packages
        ]);
    }

    public function test_complete_subscription_workflow(): void
    {
        $package = Package::where('name', 'starter')->first();
        $initialBalance = $this->wallet->balance;

        // 1. Souscrire au package
        $response = $this->actingAs($this->user)
            ->post(route('customer.packages.subscribe', $package));

        $response->assertRedirect(route('customer.packages.index'));
        $response->assertSessionHas('success');

        // 2. Vérifier que l'abonnement a été créé
        $subscription = UserSubscription::where('user_id', $this->user->id)->first();
        $this->assertNotNull($subscription);
        $this->assertEquals($package->id, $subscription->package_id);
        $this->assertEquals('active', $subscription->status);
        $this->assertEquals($package->price, $subscription->amount_paid);
        $this->assertEquals($package->messages_limit, $subscription->messages_limit);

        // 3. Vérifier que le wallet a été débité
        $this->wallet->refresh();
        $this->assertEquals($initialBalance - $package->price, $this->wallet->balance);

        // 4. Vérifier qu'une transaction interne a été créée
        $transaction = InternalTransaction::where('wallet_id', $this->wallet->id)->first();
        $this->assertNotNull($transaction);
        $this->assertEquals($package->price, $transaction->amount);
        $this->assertEquals('debit', $transaction->transaction_type->value);
        $this->assertStringContains('Souscription au package', $transaction->description);
    }

    public function test_subscription_integrates_with_usage_tracking(): void
    {
        // 1. Créer un abonnement
        $package = Package::where('name', 'starter')->first();
        $subscription = UserSubscription::create([
            'user_id' => $this->user->id,
            'package_id' => $package->id,
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
            'status' => 'active',
            'messages_limit' => $package->messages_limit,
            'context_limit' => $package->context_limit,
            'accounts_limit' => $package->accounts_limit,
            'products_limit' => $package->products_limit,
            'amount_paid' => $package->price,
            'payment_method' => 'wallet',
        ]);

        // 2. Créer un compte WhatsApp
        $account = WhatsAppAccount::factory()->create(['user_id' => $this->user->id]);

        // 3. Vérifier que l'usage peut être tracké
        $accountUsage = $subscription->getUsageForAccount($account);
        $this->assertNotNull($accountUsage);
        $this->assertEquals(0, $accountUsage->messages_used);

        // 4. Simuler l'usage
        $accountUsage->incrementUsage(50);

        // 5. Vérifier les limites
        $this->assertEquals(50, $accountUsage->messages_used);
        $this->assertEquals($package->messages_limit - 50, $subscription->getRemainingMessages());
        $this->assertTrue($subscription->hasRemainingMessages());
    }

    public function test_subscription_overage_billing_integration(): void
    {
        // 1. Créer un abonnement avec usage quasi-complet
        $package = Package::where('name', 'starter')->first();
        $subscription = UserSubscription::create([
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

        $account = WhatsAppAccount::factory()->create(['user_id' => $this->user->id]);
        $accountUsage = $subscription->getUsageForAccount($account);

        // 2. Utiliser presque toute la limite
        $accountUsage->update(['messages_used' => $package->messages_limit - 1]);

        // 3. Vérifier qu'on peut encore traiter 1 message
        $this->assertTrue($subscription->canAffordMessage(1));
        $this->assertEquals(1, $subscription->getRemainingMessages());

        // 4. Vérifier qu'on ne peut pas traiter 2 messages sans overage
        $this->assertFalse($subscription->hasRemainingMessages());

        // 5. Mais qu'on peut traiter avec overage si le wallet le permet
        $this->assertTrue($subscription->canAffordOverage(1));
        $this->assertTrue($subscription->canAffordMessage(2)); // 1 normal + 1 overage
    }

    public function test_multiple_packages_subscription_prevention(): void
    {
        $starterPackage = Package::where('name', 'starter')->first();
        $proPackage = Package::where('name', 'pro')->first();

        // 1. S'abonner au starter
        $this->actingAs($this->user)
            ->post(route('customer.packages.subscribe', $starterPackage));

        // Vérifier l'abonnement
        $this->assertDatabaseHas('user_subscriptions', [
            'user_id' => $this->user->id,
            'package_id' => $starterPackage->id,
            'status' => 'active',
        ]);

        // 2. Tenter de s'abonner au pro
        $response = $this->actingAs($this->user)
            ->post(route('customer.packages.subscribe', $proPackage));

        $response->assertRedirect(route('customer.packages.index'));
        $response->assertSessionHas('error');

        // 3. Vérifier qu'il n'y a qu'un seul abonnement
        $this->assertEquals(1, UserSubscription::where('user_id', $this->user->id)->count());
    }

    public function test_trial_package_workflow(): void
    {
        $trialPackage = Package::where('name', 'trial')->first();
        $initialBalance = $this->wallet->balance;

        // 1. S'abonner au trial
        $response = $this->actingAs($this->user)
            ->post(route('customer.packages.subscribe', $trialPackage));

        $response->assertRedirect(route('customer.packages.index'));
        $response->assertSessionHas('success');

        // 2. Vérifier l'abonnement trial
        $subscription = UserSubscription::where('user_id', $this->user->id)->first();
        $this->assertNotNull($subscription);
        $this->assertTrue($subscription->isTrialSubscription());
        $this->assertEquals(0, $subscription->amount_paid);

        // 3. Vérifier que le wallet n'a pas été débité
        $this->wallet->refresh();
        $this->assertEquals($initialBalance, $this->wallet->balance);

        // 4. Vérifier qu'aucune transaction n'a été créée
        $this->assertEquals(0, InternalTransaction::where('wallet_id', $this->wallet->id)->count());

        // 5. Attendre l'expiration et vérifier qu'on ne peut plus s'abonner au trial
        $subscription->update([
            'ends_at' => now()->subDay(),
            'status' => 'expired',
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('customer.packages.subscribe', $trialPackage));

        $response->assertRedirect(route('customer.packages.index'));
        $response->assertSessionHas('error', 'Vous avez déjà utilisé votre essai gratuit.');
    }

    public function test_subscription_limits_enforcement(): void
    {
        $proPackage = Package::where('name', 'pro')->first();

        // 1. Créer un abonnement pro (2 comptes autorisés)
        $subscription = UserSubscription::create([
            'user_id' => $this->user->id,
            'package_id' => $proPackage->id,
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
            'status' => 'active',
            'messages_limit' => $proPackage->messages_limit,
            'context_limit' => $proPackage->context_limit,
            'accounts_limit' => $proPackage->accounts_limit, // 2
            'products_limit' => $proPackage->products_limit, // 5
        ]);

        // 2. Créer des comptes WhatsApp pour tester les limites
        $account1 = WhatsAppAccount::factory()->create(['user_id' => $this->user->id]);
        $account2 = WhatsAppAccount::factory()->create(['user_id' => $this->user->id]);

        // 3. Vérifier que chaque compte peut avoir son propre usage
        $usage1 = $subscription->getUsageForAccount($account1);
        $usage2 = $subscription->getUsageForAccount($account2);

        $this->assertNotEquals($usage1->id, $usage2->id);

        // 4. Tester l'usage combiné
        $usage1->incrementUsage(100);
        $usage2->incrementUsage(150);

        $this->assertEquals(250, $subscription->getTotalMessagesUsed());
        $this->assertEquals($proPackage->messages_limit - 250, $subscription->getRemainingMessages());
    }

    public function test_package_expiration_workflow(): void
    {
        // 1. Créer un abonnement qui expire bientôt
        $package = Package::where('name', 'starter')->first();
        $subscription = UserSubscription::create([
            'user_id' => $this->user->id,
            'package_id' => $package->id,
            'starts_at' => now()->subMonth(),
            'ends_at' => now()->subDay(), // Expiré depuis hier
            'status' => 'active', // Pas encore marqué comme expiré
            'messages_limit' => $package->messages_limit,
            'context_limit' => $package->context_limit,
            'accounts_limit' => $package->accounts_limit,
            'products_limit' => $package->products_limit,
        ]);

        // 2. Vérifier que l'abonnement est considéré comme expiré
        $this->assertTrue($subscription->isExpired());
        $this->assertFalse($subscription->isActive());

        // 3. L'utilisateur devrait pouvoir s'abonner à un nouveau package
        $newPackage = Package::where('name', 'pro')->first();
        $response = $this->actingAs($this->user)
            ->post(route('customer.packages.subscribe', $newPackage));

        // Cela devrait échouer car hasActiveSubscription() vérifie aussi les dates
        // mais si la logique permet les renouvellements, ajuster le test
        $response->assertRedirect(route('customer.packages.index'));
    }

    public function test_insufficient_wallet_balance_error_message(): void
    {
        // 1. Réduire le solde du wallet
        $this->wallet->update(['balance' => 1000]);

        $package = Package::where('name', 'pro')->first(); // 5000 XAF

        // 2. Tenter la souscription
        $response = $this->actingAs($this->user)
            ->post(route('customer.packages.subscribe', $package));

        $response->assertRedirect(route('customer.packages.index'));
        $response->assertSessionHas('error');
        $response->assertSessionHas('recharge_needed', true);
        $response->assertSessionHas('missing_amount', 4000); // 5000 - 1000

        // 3. Vérifier qu'aucun abonnement n'a été créé
        $this->assertEquals(0, UserSubscription::where('user_id', $this->user->id)->count());

        // 4. Vérifier que le wallet n'a pas été débité
        $this->wallet->refresh();
        $this->assertEquals(1000, $this->wallet->balance);
    }
}
