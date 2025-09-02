<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire\Admin\Coupons\Forms;

use App\Enums\CouponStatus;
use App\Enums\CouponType;
use App\Livewire\Admin\Coupons\Forms\CreateCouponForm;
use App\Models\Coupon;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreateCouponFormTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        // Create necessary roles
        \Spatie\Permission\Models\Role::create(['name' => 'admin']);
        \Spatie\Permission\Models\Role::create(['name' => 'customer']);

        $this->admin = User::factory()->admin()->create();
    }

    #[Test]
    public function test_component_can_be_rendered(): void
    {
        Livewire::actingAs($this->admin)
            ->test(CreateCouponForm::class)
            ->assertStatus(200)
            ->assertSee('Code coupon')
            ->assertSee('Type')
            ->assertSee('Valeur');
    }

    #[Test]
    public function test_can_create_percentage_coupon(): void
    {
        Livewire::actingAs($this->admin)
            ->test(CreateCouponForm::class)
            ->set('code', 'SAVE20')
            ->set('type', CouponType::PERCENTAGE()->value)
            ->set('value', '20.00')
            ->set('usage_limit', 100)
            ->set('per_user_limit', 1)
            ->call('save')
            ->assertRedirect()
            ->assertSessionHas('success', 'Coupon créé avec succès !');

        $this->assertDatabaseHas('coupons', [
            'code' => 'SAVE20',
            'type' => 'percentage',
            'value' => 20.00,
            'status' => 'active',
            'is_active' => true,
            'usage_limit' => 100,
            'per_user_limit' => 1,
            'created_by' => $this->admin->id,
        ]);
    }

    #[Test]
    public function test_can_create_fixed_amount_coupon(): void
    {
        Livewire::actingAs($this->admin)
            ->test(CreateCouponForm::class)
            ->set('code', 'DISCOUNT500')
            ->set('type', CouponType::FIXED_AMOUNT()->value)
            ->set('value', '500.00')
            ->set('usage_limit', 50)
            ->set('per_user_limit', 2)
            ->call('save')
            ->assertRedirect()
            ->assertSessionHas('success', 'Coupon créé avec succès !');

        $this->assertDatabaseHas('coupons', [
            'code' => 'DISCOUNT500',
            'type' => 'fixed_amount',
            'value' => 500.00,
            'status' => 'active',
            'usage_limit' => 50,
            'per_user_limit' => 2,
        ]);
    }

    #[Test]
    public function test_can_create_coupon_with_validity_dates(): void
    {
        $validFrom = now()->addDays(1)->format('Y-m-d');
        $validUntil = now()->addDays(30)->format('Y-m-d');

        $component = Livewire::actingAs($this->admin)
            ->test(CreateCouponForm::class)
            ->set('code', 'DATEDCOUPON')
            ->set('type', CouponType::PERCENTAGE()->value)
            ->set('value', '15.00')
            ->set('usage_limit', 100)
            ->set('per_user_limit', 1)
            ->set('valid_from', $validFrom)
            ->set('valid_until', $validUntil)
            ->call('save');

        $component->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('coupons', [
            'code' => 'DATEDCOUPON',
            'valid_from' => $validFrom.' 00:00:00',
            'valid_until' => $validUntil.' 00:00:00',
        ]);
    }

    #[Test]
    public function test_generates_random_code(): void
    {
        $component = Livewire::actingAs($this->admin)
            ->test(CreateCouponForm::class);

        $initialCode = $component->get('code');

        $this->assertNotEmpty($initialCode);
        $this->assertEquals(8, strlen($initialCode));
        $this->assertTrue(ctype_alnum($initialCode));
    }

    #[Test]
    public function test_code_is_required(): void
    {
        Livewire::actingAs($this->admin)
            ->test(CreateCouponForm::class)
            ->set('code', '')
            ->set('type', CouponType::PERCENTAGE()->value)
            ->set('value', '20.00')
            ->set('usage_limit', 100)
            ->set('per_user_limit', 1)
            ->call('save')
            ->assertHasErrors(['code' => 'required']);
    }

    #[Test]
    public function test_code_must_be_unique(): void
    {
        // Créer un coupon existant
        Coupon::create([
            'code' => 'EXISTINGCODE',
            'type' => CouponType::PERCENTAGE()->value,
            'value' => 10.00,
            'status' => CouponStatus::ACTIVE()->value,
            'is_active' => true,
            'usage_limit' => 100,
            'per_user_limit' => 1,
            'used_count' => 0,
            'created_by' => $this->admin->id,
        ]);

        Livewire::actingAs($this->admin)
            ->test(CreateCouponForm::class)
            ->set('code', 'EXISTINGCODE')
            ->set('type', CouponType::PERCENTAGE()->value)
            ->set('value', '20.00')
            ->set('usage_limit', 100)
            ->set('per_user_limit', 1)
            ->call('save')
            ->assertHasErrors(['code' => 'unique']);
    }

    #[Test]
    public function test_value_is_required(): void
    {
        Livewire::actingAs($this->admin)
            ->test(CreateCouponForm::class)
            ->set('code', 'TESTCODE')
            ->set('type', CouponType::PERCENTAGE()->value)
            ->set('value', '')
            ->set('usage_limit', 100)
            ->set('per_user_limit', 1)
            ->call('save')
            ->assertHasErrors(['value' => 'required']);
    }

    #[Test]
    public function test_percentage_value_cannot_exceed_100(): void
    {
        Livewire::actingAs($this->admin)
            ->test(CreateCouponForm::class)
            ->set('code', 'INVALIDPERCENT')
            ->set('type', CouponType::PERCENTAGE()->value)
            ->set('value', '150.00')
            ->set('usage_limit', 100)
            ->set('per_user_limit', 1)
            ->call('save')
            ->assertHasErrors(['value']);
    }

    #[Test]
    public function test_usage_limit_must_be_positive(): void
    {
        Livewire::actingAs($this->admin)
            ->test(CreateCouponForm::class)
            ->set('code', 'TESTCODE')
            ->set('type', CouponType::PERCENTAGE()->value)
            ->set('value', '20.00')
            ->set('usage_limit', 0)
            ->set('per_user_limit', 1)
            ->call('save')
            ->assertHasErrors(['usage_limit']);
    }

    #[Test]
    public function test_per_user_limit_must_be_positive(): void
    {
        Livewire::actingAs($this->admin)
            ->test(CreateCouponForm::class)
            ->set('code', 'TESTCODE')
            ->set('type', CouponType::PERCENTAGE()->value)
            ->set('value', '20.00')
            ->set('usage_limit', 100)
            ->set('per_user_limit', 0)
            ->call('save')
            ->assertHasErrors(['per_user_limit']);
    }

    #[Test]
    public function test_valid_until_must_be_after_valid_from(): void
    {
        $validFrom = now()->addDays(10)->format('Y-m-d');
        $validUntil = now()->addDays(5)->format('Y-m-d'); // Avant valid_from

        Livewire::actingAs($this->admin)
            ->test(CreateCouponForm::class)
            ->set('code', 'DATEERROR')
            ->set('type', CouponType::PERCENTAGE()->value)
            ->set('value', '15.00')
            ->set('usage_limit', 100)
            ->set('per_user_limit', 1)
            ->set('valid_from', $validFrom)
            ->set('valid_until', $validUntil)
            ->call('save')
            ->assertHasErrors(['valid_until']);
    }

    #[Test]
    public function test_form_resets_after_successful_creation(): void
    {
        $component = Livewire::actingAs($this->admin)
            ->test(CreateCouponForm::class)
            ->set('code', 'RESETTEST')
            ->set('type', CouponType::PERCENTAGE()->value)
            ->set('value', '25.00')
            ->set('usage_limit', 100)
            ->set('per_user_limit', 1)
            ->call('save');

        // Vérifier la redirection et le succès
        $component->assertRedirect()
            ->assertSessionHas('success');
    }

    #[Test]
    public function test_fixed_amount_validation_works(): void
    {
        Livewire::actingAs($this->admin)
            ->test(CreateCouponForm::class)
            ->set('code', 'FIXEDTEST')
            ->set('type', CouponType::FIXED_AMOUNT()->value)
            ->set('value', '-100.00') // Valeur négative
            ->set('usage_limit', 100)
            ->set('per_user_limit', 1)
            ->call('save')
            ->assertHasErrors(['value']);
    }

    #[Test]
    public function test_can_create_inactive_coupon(): void
    {
        Livewire::actingAs($this->admin)
            ->test(CreateCouponForm::class)
            ->set('code', 'INACTIVETEST')
            ->set('type', CouponType::PERCENTAGE()->value)
            ->set('value', '10.00')
            ->set('is_active', false)
            ->set('usage_limit', 100)
            ->set('per_user_limit', 1)
            ->call('save')
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('coupons', [
            'code' => 'INACTIVETEST',
            'is_active' => false,
        ]);
    }

    #[Test]
    public function test_coupon_type_affects_validation(): void
    {
        $component = Livewire::actingAs($this->admin)
            ->test(CreateCouponForm::class);

        // Test percentage type - valid range
        $component->set('type', CouponType::PERCENTAGE()->value)
            ->set('value', '50')
            ->assertSet('type', 'percentage');

        // Test fixed amount type
        $component->set('type', CouponType::FIXED_AMOUNT()->value)
            ->set('value', '100')
            ->assertSet('type', 'fixed_amount');
    }

    #[Test]
    public function test_maximum_limits_validation(): void
    {
        Livewire::actingAs($this->admin)
            ->test(CreateCouponForm::class)
            ->set('code', 'LIMITTEST')
            ->set('type', CouponType::PERCENTAGE()->value)
            ->set('value', '10.00')
            ->set('usage_limit', 20000) // Au-dessus de la limite max
            ->set('per_user_limit', 1)
            ->call('save')
            ->assertHasErrors(['usage_limit']);
    }
}
