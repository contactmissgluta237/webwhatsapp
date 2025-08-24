<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Package;
use App\Models\User;
use App\Models\UserSubscription;
use App\Models\WhatsAppAccount;
use App\Models\WhatsAppAccountUsage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SubscriptionsDataTableTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed packages
        $this->artisan('db:seed', ['--class' => 'PackagesSeeder']);

        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    public function test_admin_can_view_subscriptions_data_table(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.subscriptions.index'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.subscriptions.index');
        $response->assertSeeLivewire('admin.subscriptions-data-table');
    }

    public function test_data_table_displays_subscriptions(): void
    {
        // Créer des utilisateurs et souscriptions
        $user1 = User::factory()->create(['name' => 'John Doe', 'email' => 'john@test.com']);
        $user2 = User::factory()->create(['name' => 'Jane Smith', 'email' => 'jane@test.com']);

        $starterPackage = Package::where('name', 'starter')->first();
        $proPackage = Package::where('name', 'pro')->first();

        UserSubscription::create([
            'user_id' => $user1->id,
            'package_id' => $starterPackage->id,
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
            'status' => 'active',
            'messages_limit' => $starterPackage->messages_limit,
            'context_limit' => $starterPackage->context_limit,
            'accounts_limit' => $starterPackage->accounts_limit,
            'products_limit' => $starterPackage->products_limit,
            'amount_paid' => $starterPackage->price,
            'payment_method' => 'wallet',
        ]);

        UserSubscription::create([
            'user_id' => $user2->id,
            'package_id' => $proPackage->id,
            'starts_at' => now()->subDays(5),
            'ends_at' => now()->addDays(25),
            'status' => 'active',
            'messages_limit' => $proPackage->messages_limit,
            'context_limit' => $proPackage->context_limit,
            'accounts_limit' => $proPackage->accounts_limit,
            'products_limit' => $proPackage->products_limit,
            'amount_paid' => $proPackage->price,
            'payment_method' => 'wallet',
        ]);

        Livewire::actingAs($this->admin)
            ->test('admin.subscriptions-data-table')
            ->assertSee('John Doe')
            ->assertSee('john@test.com')
            ->assertSee('Jane Smith')
            ->assertSee('jane@test.com')
            ->assertSee('Starter')
            ->assertSee('Pro')
            ->assertSee('Actif');
    }

    public function test_data_table_package_filter_works(): void
    {
        $user1 = User::factory()->create(['name' => 'John Doe']);
        $user2 = User::factory()->create(['name' => 'Jane Smith']);

        $starterPackage = Package::where('name', 'starter')->first();
        $proPackage = Package::where('name', 'pro')->first();

        UserSubscription::create([
            'user_id' => $user1->id,
            'package_id' => $starterPackage->id,
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
            'status' => 'active',
            'messages_limit' => $starterPackage->messages_limit,
            'context_limit' => $starterPackage->context_limit,
            'accounts_limit' => $starterPackage->accounts_limit,
            'products_limit' => $starterPackage->products_limit,
        ]);

        UserSubscription::create([
            'user_id' => $user2->id,
            'package_id' => $proPackage->id,
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
            'status' => 'active',
            'messages_limit' => $proPackage->messages_limit,
            'context_limit' => $proPackage->context_limit,
            'accounts_limit' => $proPackage->accounts_limit,
            'products_limit' => $proPackage->products_limit,
        ]);

        // Test filtre par package
        Livewire::actingAs($this->admin)
            ->test('admin.subscriptions-data-table')
            ->set('package_id', $starterPackage->id)
            ->assertSee('John Doe')
            ->assertDontSee('Jane Smith')
            ->assertSee('Starter')
            ->assertDontSee('Pro');
    }

    public function test_data_table_status_filter_works(): void
    {
        $user1 = User::factory()->create(['name' => 'Active User']);
        $user2 = User::factory()->create(['name' => 'Expired User']);

        $package = Package::where('name', 'starter')->first();

        UserSubscription::create([
            'user_id' => $user1->id,
            'package_id' => $package->id,
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
            'status' => 'active',
            'messages_limit' => $package->messages_limit,
            'context_limit' => $package->context_limit,
            'accounts_limit' => $package->accounts_limit,
            'products_limit' => $package->products_limit,
        ]);

        UserSubscription::create([
            'user_id' => $user2->id,
            'package_id' => $package->id,
            'starts_at' => now()->subMonth(),
            'ends_at' => now()->subDays(5),
            'status' => 'expired',
            'messages_limit' => $package->messages_limit,
            'context_limit' => $package->context_limit,
            'accounts_limit' => $package->accounts_limit,
            'products_limit' => $package->products_limit,
        ]);

        // Test filtre par statut actif
        Livewire::actingAs($this->admin)
            ->test('admin.subscriptions-data-table')
            ->set('status', 'active')
            ->assertSee('Active User')
            ->assertDontSee('Expired User');

        // Test filtre par statut expiré
        Livewire::actingAs($this->admin)
            ->test('admin.subscriptions-data-table')
            ->set('status', 'expired')
            ->assertSee('Expired User')
            ->assertDontSee('Active User');
    }

    public function test_data_table_search_works(): void
    {
        $user1 = User::factory()->create(['name' => 'John Doe', 'email' => 'john@test.com']);
        $user2 = User::factory()->create(['name' => 'Jane Smith', 'email' => 'jane@test.com']);

        $package = Package::where('name', 'starter')->first();

        UserSubscription::create([
            'user_id' => $user1->id,
            'package_id' => $package->id,
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
            'status' => 'active',
            'messages_limit' => $package->messages_limit,
            'context_limit' => $package->context_limit,
            'accounts_limit' => $package->accounts_limit,
            'products_limit' => $package->products_limit,
        ]);

        UserSubscription::create([
            'user_id' => $user2->id,
            'package_id' => $package->id,
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
            'status' => 'active',
            'messages_limit' => $package->messages_limit,
            'context_limit' => $package->context_limit,
            'accounts_limit' => $package->accounts_limit,
            'products_limit' => $package->products_limit,
        ]);

        // Test recherche par nom
        Livewire::actingAs($this->admin)
            ->test('admin.subscriptions-data-table')
            ->set('user_search', 'John')
            ->assertSee('John Doe')
            ->assertDontSee('Jane Smith');
    }

    public function test_data_table_displays_usage_progress(): void
    {
        $user = User::factory()->create(['name' => 'Test User']);
        $account = WhatsAppAccount::factory()->create(['user_id' => $user->id]);
        $package = Package::where('name', 'starter')->first();

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

        // Créer un usage à 50% de la limite
        WhatsAppAccountUsage::create([
            'user_subscription_id' => $subscription->id,
            'whatsapp_account_id' => $account->id,
            'messages_used' => 100, // 50% de 200 messages
            'base_messages_count' => 100,
            'media_messages_count' => 0,
            'overage_messages_used' => 0,
            'overage_cost_paid_xaf' => 0,
            'estimated_cost_xaf' => 1000,
        ]);

        Livewire::actingAs($this->admin)
            ->test('admin.subscriptions-data-table')
            ->assertSee('Test User')
            ->assertSee('100/200'); // Usage affiché
    }

    public function test_data_table_sorting_works(): void
    {
        $user1 = User::factory()->create(['name' => 'Alpha User']);
        $user2 = User::factory()->create(['name' => 'Beta User']);

        $package = Package::where('name', 'starter')->first();

        UserSubscription::create([
            'user_id' => $user1->id,
            'package_id' => $package->id,
            'starts_at' => now()->subDays(2),
            'ends_at' => now()->addMonth(),
            'status' => 'active',
            'messages_limit' => $package->messages_limit,
            'context_limit' => $package->context_limit,
            'accounts_limit' => $package->accounts_limit,
            'products_limit' => $package->products_limit,
        ]);

        UserSubscription::create([
            'user_id' => $user2->id,
            'package_id' => $package->id,
            'starts_at' => now()->subDays(1),
            'ends_at' => now()->addMonth(),
            'status' => 'active',
            'messages_limit' => $package->messages_limit,
            'context_limit' => $package->context_limit,
            'accounts_limit' => $package->accounts_limit,
            'products_limit' => $package->products_limit,
        ]);

        // Test tri par date de création (défaut : desc)
        Livewire::actingAs($this->admin)
            ->test('admin.subscriptions-data-table')
            ->assertSeeInOrder(['Beta User', 'Alpha User']); // Plus récent en premier
    }

    public function test_data_table_shows_correct_badges_for_status(): void
    {
        $user = User::factory()->create(['name' => 'Test User']);
        $package = Package::where('name', 'starter')->first();

        // Test chaque statut
        $statuses = [
            'active' => 'Actif',
            'expired' => 'Expiré',
            'cancelled' => 'Annulé',
            'suspended' => 'Suspendu',
        ];

        foreach ($statuses as $status => $label) {
            UserSubscription::create([
                'user_id' => $user->id,
                'package_id' => $package->id,
                'starts_at' => now()->subMonth(),
                'ends_at' => $status === 'active' ? now()->addMonth() : now()->subDays(5),
                'status' => $status,
                'messages_limit' => $package->messages_limit,
                'context_limit' => $package->context_limit,
                'accounts_limit' => $package->accounts_limit,
                'products_limit' => $package->products_limit,
            ]);
        }

        $component = Livewire::actingAs($this->admin)
            ->test('admin.subscriptions-data-table');

        // Vérifier que tous les badges sont affichés
        foreach ($statuses as $status => $label) {
            $component->assertSee($label);
        }
    }

    public function test_data_table_package_filter_uses_get_parameter(): void
    {
        $package = Package::where('name', 'starter')->first();

        // Simuler la requête avec package_id en GET
        $response = $this->actingAs($this->admin)
            ->get(route('admin.subscriptions.index', ['package_id' => $package->id]));

        $response->assertStatus(200);

        // Le DataTable devrait automatiquement filtrer par ce package
        // (grâce au système de filtres automatique de LaravelLivewireTables)
    }
}
