<?php

namespace Tests\Feature\Customer;

use App\Enums\PermissionEnum;
use App\Enums\UserRole;
use App\Livewire\Customer\CreateCustomerRechargeForm;
use App\Livewire\Customer\CreateCustomerWithdrawalForm;
use App\Models\Geography\Country;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TransactionPagesTest extends TestCase
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
    public function customer_can_access_recharge_page(): void
    {
        $customer = $this->createCustomerWithWallet();

        $this->actingAs($customer)
            ->get(route('customer.transactions.recharge'))
            ->assertOk()
            ->assertSee('Recharger mon compte')
            ->assertSee('Nouvelle recharge');
    }

    #[Test]
    public function customer_can_access_withdrawal_page(): void
    {
        $customer = $this->createCustomerWithWallet();

        $this->actingAs($customer)
            ->get(route('customer.transactions.withdrawal'))
            ->assertOk()
            ->assertSee('Faire un retrait')
            ->assertSee('Nouveau retrait');
    }

    #[Test]
    public function customer_can_see_balance_on_recharge_page(): void
    {
        $customer = $this->createCustomerWithWallet(1500.00);

        $this->actingAs($customer)
            ->get(route('customer.transactions.recharge'))
            ->assertOk()
            ->assertSee('1 500 XAF'); // Utilise le format de devise XAF
    }

    #[Test]
    public function customer_can_see_balance_on_withdrawal_page(): void
    {
        $customer = $this->createCustomerWithWallet(2500.00);

        $this->actingAs($customer)
            ->get(route('customer.transactions.withdrawal'))
            ->assertOk()
            ->assertSee('2 500 XAF'); // Utilise le format de devise XAF
    }

    #[Test]
    public function recharge_form_displays_predefined_amounts(): void
    {
        $customer = $this->createCustomerWithWallet();

        $this->actingAs($customer);

        $component = Livewire::test(CreateCustomerRechargeForm::class);

        // Vérifier que les montants prédéfinis sont présents
        $predefinedAmounts = config('system_settings.predefined_amounts', [1000, 5000, 10000, 25000]);

        foreach ($predefinedAmounts as $amount) {
            $component->assertSee(number_format($amount, 0, ',', ' '));
        }
    }

    #[Test]
    public function withdrawal_form_displays_predefined_amounts(): void
    {
        $customer = $this->createCustomerWithWallet(50000);

        $this->actingAs($customer);

        $component = Livewire::test(CreateCustomerWithdrawalForm::class);

        // Vérifier que les montants prédéfinis sont présents
        $predefinedAmounts = config('system_settings.predefined_amounts', [1000, 5000, 10000, 25000]);

        foreach ($predefinedAmounts as $amount) {
            $component->assertSee(number_format($amount, 0, ',', ' '));
        }
    }

    #[Test]
    public function recharge_form_calculates_fees_correctly(): void
    {
        $customer = $this->createCustomerWithWallet();

        $this->actingAs($customer);

        $component = Livewire::test(CreateCustomerRechargeForm::class)
            ->set('amount', 10000);

        // Vérifier que les frais sont calculés (selon la config système)
        $feePercentage = config('system_settings.fees.recharge', 0);
        $expectedFee = (10000 * $feePercentage) / 100;
        $expectedTotal = 10000 + $expectedFee;

        $component->assertSet('feeAmount', $expectedFee)
            ->assertSet('totalToPay', $expectedTotal);
    }

    #[Test]
    public function withdrawal_form_calculates_fees_correctly(): void
    {
        $customer = $this->createCustomerWithWallet(50000);

        $this->actingAs($customer);

        $component = Livewire::test(CreateCustomerWithdrawalForm::class)
            ->set('amount', 10000);

        // Vérifier que les frais sont calculés (selon la config système)
        $feePercentage = config('system_settings.fees.withdrawal', 0);
        $expectedFee = (10000 * $feePercentage) / 100;
        $expectedFinalAmount = 10000 - $expectedFee;

        $component->assertSet('feeAmount', $expectedFee)
            ->assertSet('finalAmount', $expectedFinalAmount);
    }

    #[Test]
    public function guest_cannot_access_transaction_pages(): void
    {
        $this->get(route('customer.transactions.recharge'))
            ->assertRedirect('/login');

        $this->get(route('customer.transactions.withdrawal'))
            ->assertRedirect('/login');
    }

    #[Test]
    public function admin_cannot_access_customer_transaction_pages(): void
    {
        $admin = User::factory()->create([
            'country_id' => 1,
            'currency' => 'XAF',
        ]);
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->get(route('customer.transactions.recharge'))
            ->assertForbidden();

        $this->actingAs($admin)
            ->get(route('customer.transactions.withdrawal'))
            ->assertForbidden();
    }

    #[Test]
    public function recharge_form_validation_requires_fields(): void
    {
        $customer = $this->createCustomerWithWallet();

        $this->actingAs($customer);

        // Test validation without amount
        $component = Livewire::test(CreateCustomerRechargeForm::class)
            ->call('createRecharge');

        $component->assertSet('error', 'Erreur : Veuillez sélectionner un montant.');
    }

    #[Test]
    public function recharge_form_reset_clears_all_fields(): void
    {
        $customer = $this->createCustomerWithWallet();

        $this->actingAs($customer);

        $component = Livewire::test(CreateCustomerRechargeForm::class)
            ->set('amount', 1000)
            ->set('payment_method', 'mobile_money')
            ->call('resetForm');

        $component->assertSet('amount', '')
            ->assertSet('payment_method', '')
            ->assertSet('feeAmount', null)
            ->assertSet('totalToPay', null);
    }

    #[Test]
    public function recharge_form_prefills_user_phone_number(): void
    {
        $customer = $this->createCustomerWithWallet();
        $customer->update(['phone_number' => '+237670000001']);

        $this->actingAs($customer);

        $component = Livewire::test(CreateCustomerRechargeForm::class);

        $component->assertSet('sender_account', '+237670000001')
            ->assertSet('phone_number', '+237670000001');
    }

    private function createCustomerWithWallet(float $balance = 1000.00): User
    {
        $customer = User::factory()->create([
            'country_id' => 1,
            'currency' => 'XAF',
        ]);
        $customer->assignRole('customer');

        Wallet::create([
            'user_id' => $customer->id,
            'balance' => $balance,
        ]);

        return $customer;
    }
}
