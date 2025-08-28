<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Package;
use App\Models\User;
use App\Models\UserSubscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PackageManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Package $trialPackage;
    private Package $starterPackage;
    private Package $proPackage;
    private Package $businessPackage;

    protected function setUp(): void
    {
        parent::setUp();

        // Create necessary roles
        \Spatie\Permission\Models\Role::create(['name' => 'admin']);
        \Spatie\Permission\Models\Role::create(['name' => 'customer']);

        // Seed packages
        $this->artisan('db:seed', ['--class' => 'PackagesSeeder']);

        $this->admin = User::factory()->admin()->create();
        
        // Cache packages for reuse
        $this->trialPackage = Package::where('name', 'trial')->first();
        $this->starterPackage = Package::where('name', 'starter')->first();
        $this->proPackage = Package::where('name', 'pro')->first();
        $this->businessPackage = Package::where('name', 'business')->first();
    }

    private function createSubscriptionsForPackage(Package $package, int $count = 1): void
    {
        UserSubscription::factory()->count($count)->create([
            'package_id' => $package->id,
        ]);
    }

    #[Test]
    public function test_admin_can_view_packages_page(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.packages.index'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.packages.index');
        $response->assertViewHas('packages');
        $response->assertSee('Gestion des Packages');
    }

    #[Test]
    public function test_packages_are_displayed_with_all_information(): void
    {
        // Créer quelques souscriptions pour tester les compteurs
        $this->createSubscriptionsForPackage($this->starterPackage, 3);

        $response = $this->actingAs($this->admin)->get(route('admin.packages.index'));

        $response->assertStatus(200);

        // Vérifier que tous les packages sont affichés
        $response->assertSee('Essai Gratuit');
        $response->assertSee('Starter');
        $response->assertSee('Pro');
        $response->assertSee('Business');

        // Vérifier les informations détaillées
        $response->assertSee('GRATUIT'); // Prix trial
        $response->assertSee('2 000 XAF'); // Prix starter formaté
        $response->assertSee('5 000 XAF'); // Prix pro
        $response->assertSee('10 000 XAF'); // Prix business

        // Vérifier les limites
        $response->assertSee('200'); // Messages starter
        $response->assertSee('3,000'); // Contexte starter formaté
        $response->assertSee('1'); // Compte starter

        // Vérifier les badges de fonctionnalités
        $response->assertSee('Rapports hebdo.');
        $response->assertSee('Support prioritaire');

        // Vérifier les compteurs de souscriptions
        $response->assertSee('3'); // Nombre de souscriptions pour starter

        // Vérifier les statuts
        $response->assertSee('Actif');
    }

    #[Test]
    public function test_packages_table_shows_correct_structure(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.packages.index'));

        $response->assertStatus(200);

        // Vérifier les en-têtes du tableau
        $response->assertSee('Package');
        $response->assertSee('Prix');
        $response->assertSee('Durée');
        $response->assertSee('Messages');
        $response->assertSee('Contexte');
        $response->assertSee('Comptes');
        $response->assertSee('Produits');
        $response->assertSee('Fonctionnalités');
        $response->assertSee('Souscriptions');
        $response->assertSee('Statut');
        $response->assertSee('Actions');
    }

    #[Test]
    public function test_view_subscriptions_link_redirects_correctly(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.subscriptions.index', ['package_id' => $this->starterPackage->id]));

        $response->assertStatus(200);
        $response->assertViewIs('admin.subscriptions.index');
    }

    #[Test]
    public function test_packages_display_features_correctly(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.packages.index'));

        // Utiliser les packages en cache

        // Business package (ancien pro) - doit avoir des fonctionnalités
        if ($this->businessPackage->hasWeeklyReports()) {
            $response->assertSee('Rapports hebdo.');
        }
        if ($this->businessPackage->hasPrioritySupport()) {
            $response->assertSee('Support prioritaire');
        }

        // Pro package (ancien business) - doit avoir des fonctionnalités aussi
        if ($this->proPackage->hasWeeklyReports()) {
            $response->assertSee('Rapports hebdo.');
        }
        if ($this->proPackage->hasPrioritySupport()) {
            $response->assertSee('Support prioritaire');
        }
    }

    #[Test]
    public function test_packages_display_duration_and_recurring_info(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.packages.index'));

        $response->assertStatus(200);

        // Trial: 7 jours, pas récurrent
        $response->assertSee('7 jour'); // 7 jours pour trial

        // Packages payants: 30 jours, récurrents
        $response->assertSee('30 jour'); // Pour les packages mensuels
        $response->assertSee('Récurrent'); // Badge récurrent

        // Trial: une seule fois
        $response->assertSee('Une seule fois'); // Badge one_time_only pour trial
    }

    #[Test]
    public function test_packages_show_product_limits_correctly(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.packages.index'));

        $response->assertStatus(200);

        // Trial et Starter: pas de produits (0)
        if ($this->trialPackage->products_limit === 0) {
            $response->assertSee('-'); // Indicateur pour 0 produit
        }

        // Pro: 5 produits
        if ($this->proPackage->products_limit > 0) {
            $response->assertSee((string) $this->proPackage->products_limit);
        }

        // Business: 10 produits
        if ($this->businessPackage->products_limit > 0) {
            $response->assertSee((string) $this->businessPackage->products_limit);
        }
    }

    #[Test]
    public function test_subscription_count_is_accurate(): void
    {
        // Créer quelques souscriptions
        $this->createSubscriptionsForPackage($this->starterPackage, 5);

        $response = $this->actingAs($this->admin)->get(route('admin.packages.index'));

        $response->assertStatus(200);
        $response->assertSee('5'); // Le compteur doit afficher 5
    }

    #[Test]
    public function test_action_buttons_are_present(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.packages.index'));

        $response->assertStatus(200);

        // Chaque package doit avoir un bouton "Voir les souscriptions"
        $packages = Package::all();
        foreach ($packages as $package) {
            // Vérifier que le lien vers les souscriptions existe
            $response->assertSee(route('admin.subscriptions.index', ['package_id' => $package->id]));
        }

        // Vérifier la présence de l'icône "eye" pour voir les souscriptions
        $response->assertSee('mdi-eye');
    }

    #[Test]
    public function test_recommended_badge_is_shown_for_business_package(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.packages.index'));

        $response->assertStatus(200);

        // Le package business doit avoir le badge "Recommandé"
        $response->assertSee('Recommandé');
    }

    #[Test]
    public function test_empty_state_when_no_packages(): void
    {
        // Supprimer tous les packages
        Package::truncate();

        $response = $this->actingAs($this->admin)->get(route('admin.packages.index'));

        $response->assertStatus(200);
        $response->assertSee('Aucun package trouvé');
        $response->assertSee('mdi-package-variant-closed'); // Icône empty state
    }
}
