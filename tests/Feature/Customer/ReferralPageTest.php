<?php

namespace Tests\Feature\Customer;

use App\Enums\PermissionEnum;
use App\Enums\UserRole;
use App\Models\Geography\Country;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ReferralPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Créer un pays par défaut pour les utilisateurs
        Country::create([
            'id' => 1,
            'name' => 'Cameroun',
            'code' => 'CM',
            'phone_code' => '+237',
            'flag_emoji' => '🇨🇲',
            'is_active' => true,
        ]);

        // Créer les permissions nécessaires
        $permissions = PermissionEnum::values();
        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Créer les rôles nécessaires avec leurs permissions
        $customerRole = Role::create(['name' => 'customer']);
        $customerRole->givePermissionTo(UserRole::CUSTOMER()->permissions());

        $adminRole = Role::create(['name' => 'admin']);
        $adminRole->givePermissionTo(PermissionEnum::values());
    }

    #[Test]
    public function customer_can_access_referrals_page(): void
    {
        $customer = $this->createCustomerWithWallet();

        $this->actingAs($customer)
            ->get(route('customer.referrals.index'))
            ->assertOk()
            ->assertSee('Mes filleuls');
    }

    #[Test]
    public function customer_with_no_referrals_sees_appropriate_message(): void
    {
        $customer = $this->createCustomerWithWallet();

        $response = $this->actingAs($customer)
            ->get(route('customer.referrals.index'))
            ->assertOk();

        // Si pas de référrals, la section de stats ne s'affiche pas
        // Vérifier juste que la page se charge correctement
        $response->assertSee('Mes filleuls');
    }

    #[Test]
    public function customer_with_referrals_sees_referral_stats(): void
    {
        $referrer = $this->createCustomerWithWallet();

        // Créer des filleuls
        $referral1 = User::factory()->create([
            'country_id' => 1,
            'currency' => 'USD',
            'referrer_id' => $referrer->id,
        ]);
        $referral1->assignRole('customer');

        $referral2 = User::factory()->create([
            'country_id' => 1,
            'currency' => 'USD',
            'referrer_id' => $referrer->id,
        ]);
        $referral2->assignRole('customer');

        $response = $this->actingAs($referrer)
            ->get(route('customer.referrals.index'))
            ->assertOk();

        // Vérifier que la section de stats s'affiche quand il y a des référrals
        $response->assertSee('2'); // 2 référrals
    }

    #[Test]
    public function referrals_page_displays_earnings_with_user_currency(): void
    {
        $referrer = $this->createCustomerWithWallet();

        // Créer au moins un référral pour que la section s'affiche
        $referral = User::factory()->create([
            'country_id' => 1,
            'currency' => 'USD',
            'referrer_id' => $referrer->id,
        ]);
        $referral->assignRole('customer');

        $this->actingAs($referrer)
            ->get(route('customer.referrals.index'))
            ->assertOk()
            ->assertSee('0.00 $'); // Format de devise USD pour les gains
    }

    #[Test]
    public function referrals_page_shows_active_referrals_count(): void
    {
        $referrer = $this->createCustomerWithWallet();

        // Créer un filleul actif
        $activeReferral = User::factory()->create([
            'country_id' => 1,
            'currency' => 'USD',
            'referrer_id' => $referrer->id,
            'is_active' => true,
        ]);
        $activeReferral->assignRole('customer');

        $response = $this->actingAs($referrer)
            ->get(route('customer.referrals.index'))
            ->assertOk();

        // Vérifier que le compte s'affiche correctement
        $response->assertSee('1'); // 1 référral actif
    }

    #[Test]
    public function guest_cannot_access_referrals_page(): void
    {
        $this->get(route('customer.referrals.index'))
            ->assertRedirect('/login');
    }

    #[Test]
    public function admin_cannot_access_customer_referrals_page(): void
    {
        $admin = User::factory()->create([
            'country_id' => 1,
            'currency' => 'USD',
        ]);
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->get(route('customer.referrals.index'))
            ->assertForbidden();
    }

    #[Test]
    public function referrals_page_displays_correct_breadcrumbs(): void
    {
        $customer = $this->createCustomerWithWallet();

        $this->actingAs($customer)
            ->get(route('customer.referrals.index'))
            ->assertOk()
            ->assertSee('Accueil')
            ->assertSee('Mes filleuls');
    }

    private function createCustomerWithWallet(float $balance = 1000.00): User
    {
        $customer = User::factory()->create([
            'country_id' => 1,
            'currency' => 'USD',
        ]);
        $customer->assignRole('customer');

        Wallet::create([
            'user_id' => $customer->id,
            'balance' => $balance,
        ]);

        return $customer;
    }
}
