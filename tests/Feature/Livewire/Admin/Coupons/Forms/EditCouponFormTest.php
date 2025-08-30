<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire\Admin\Coupons\Forms;

use App\Enums\CouponStatus;
use App\Enums\CouponType;
use App\Livewire\Admin\Coupons\Forms\EditCouponForm;
use App\Models\Coupon;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EditCouponFormTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Coupon $coupon;

    protected function setUp(): void
    {
        parent::setUp();

        // Create necessary roles
        \Spatie\Permission\Models\Role::create(['name' => 'admin']);
        \Spatie\Permission\Models\Role::create(['name' => 'customer']);

        $this->admin = User::factory()->admin()->create();

        $this->coupon = Coupon::create([
            'code' => 'EDIT_TEST',
            'type' => CouponType::PERCENTAGE()->value,
            'value' => 15.00,
            'status' => CouponStatus::ACTIVE()->value,
            'is_active' => true,
            'usage_limit' => 100,
            'per_user_limit' => 1,
            'used_count' => 0,
            'created_by' => $this->admin->id,
        ]);
    }

    #[Test]
    public function test_component_can_be_rendered(): void
    {
        Livewire::actingAs($this->admin)
            ->test(EditCouponForm::class, ['couponId' => $this->coupon->id])
            ->assertStatus(200)
            ->assertSee('Code coupon')
            ->assertSee('EDIT_TEST')
            ->assertSet('code', 'EDIT_TEST')
            ->assertSet('type', 'percentage')
            ->assertSet('value', 15.00);
    }

    #[Test]
    public function test_form_loads_existing_coupon_data(): void
    {
        $coupon = Coupon::create([
            'code' => 'LOAD_TEST',
            'type' => CouponType::FIXED_AMOUNT()->value,
            'value' => 500.00,
            'status' => CouponStatus::INACTIVE()->value,
            'is_active' => false,
            'usage_limit' => 50,
            'per_user_limit' => 2,
            'used_count' => 5,
            'valid_from' => now()->addDays(1),
            'valid_until' => now()->addDays(30),
            'created_by' => $this->admin->id,
        ]);

        Livewire::actingAs($this->admin)
            ->test(EditCouponForm::class, ['couponId' => $coupon->id])
            ->assertSet('code', 'LOAD_TEST')
            ->assertSet('type', 'fixed_amount')
            ->assertSet('value', 500.00)
            ->assertSet('status', 'inactive')
            ->assertSet('usageLimit', 50)
            ->assertSet('perUserLimit', 2)
            ->assertSet('validFrom', $coupon->valid_from->format('Y-m-d'))
            ->assertSet('validUntil', $coupon->valid_until->format('Y-m-d'));
    }

    #[Test]
    public function test_can_update_coupon_details(): void
    {
        Livewire::actingAs($this->admin)
            ->test(EditCouponForm::class, ['couponId' => $this->coupon->id])
            ->set('code', 'UPDATED_CODE')
            ->set('type', CouponType::FIXED_AMOUNT()->value)
            ->set('value', 1000.00)
            ->set('status', CouponStatus::INACTIVE()->value)
            ->set('usageLimit', 200)
            ->set('perUserLimit', 3)
            ->call('save')
            ->assertRedirect()
            ->assertSessionHas('success', 'Coupon mis à jour avec succès !');

        $this->coupon->refresh();

        $this->assertEquals('UPDATED_CODE', $this->coupon->code);
        $this->assertEquals('fixed_amount', $this->coupon->type->value);
        $this->assertEquals(1000.00, $this->coupon->value);
        $this->assertEquals('inactive', $this->coupon->status->value);
        $this->assertEquals(200, $this->coupon->usage_limit);
        $this->assertEquals(3, $this->coupon->per_user_limit);
        $this->assertFalse($this->coupon->is_active);
    }

    #[Test]
    public function test_can_update_validity_dates(): void
    {
        $newValidFrom = now()->addDays(5)->format('Y-m-d');
        $newValidUntil = now()->addDays(35)->format('Y-m-d');

        Livewire::actingAs($this->admin)
            ->test(EditCouponForm::class, ['couponId' => $this->coupon->id])
            ->set('validFrom', $newValidFrom)
            ->set('validUntil', $newValidUntil)
            ->call('save')
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->coupon->refresh();

        $this->assertEquals($newValidFrom, $this->coupon->valid_from->format('Y-m-d'));
        $this->assertEquals($newValidUntil, $this->coupon->valid_until->format('Y-m-d'));
    }

    #[Test]
    public function test_can_remove_validity_dates(): void
    {
        // Créer un coupon avec des dates
        $coupon = Coupon::create([
            'code' => 'REMOVE_DATES',
            'type' => CouponType::PERCENTAGE()->value,
            'value' => 20.00,
            'status' => CouponStatus::ACTIVE()->value,
            'is_active' => true,
            'usage_limit' => 100,
            'per_user_limit' => 1,
            'used_count' => 0,
            'valid_from' => now()->addDays(1),
            'valid_until' => now()->addDays(30),
            'created_by' => $this->admin->id,
        ]);

        Livewire::actingAs($this->admin)
            ->test(EditCouponForm::class, ['couponId' => $coupon->id])
            ->set('validFrom', '')
            ->set('validUntil', '')
            ->call('save')
            ->assertRedirect()
            ->assertSessionHas('success');

        $coupon->refresh();

        $this->assertNull($coupon->valid_from);
        $this->assertNull($coupon->valid_until);
    }

    #[Test]
    public function test_cannot_change_code_to_existing_one(): void
    {
        // Créer un autre coupon avec un code existant
        Coupon::create([
            'code' => 'EXISTING_CODE',
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
            ->test(EditCouponForm::class, ['couponId' => $this->coupon->id])
            ->set('code', 'EXISTING_CODE')
            ->call('save')
            ->assertHasErrors(['code' => 'unique']);
    }

    #[Test]
    public function test_can_keep_same_code(): void
    {
        Livewire::actingAs($this->admin)
            ->test(EditCouponForm::class, ['couponId' => $this->coupon->id])
            ->set('code', 'EDIT_TEST') // Même code
            ->set('value', 25.00) // Changer autre chose
            ->call('save')
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->coupon->refresh();
        $this->assertEquals('EDIT_TEST', $this->coupon->code);
        $this->assertEquals(25.00, $this->coupon->value);
    }

    #[Test]
    public function test_validation_rules_apply_on_update(): void
    {
        Livewire::actingAs($this->admin)
            ->test(EditCouponForm::class, ['couponId' => $this->coupon->id])
            ->set('code', '') // Code requis
            ->set('value', 150.00) // Au-dessus de 100% pour percentage
            ->set('usageLimit', 0) // Invalide
            ->call('save')
            ->assertHasErrors(['code', 'value', 'usageLimit']);
    }

    #[Test]
    public function test_cannot_reduce_usage_limit_below_used_count(): void
    {
        // Créer un coupon déjà utilisé
        $usedCoupon = Coupon::create([
            'code' => 'USED_COUPON',
            'type' => CouponType::PERCENTAGE()->value,
            'value' => 10.00,
            'status' => CouponStatus::ACTIVE()->value,
            'is_active' => true,
            'usage_limit' => 100,
            'per_user_limit' => 1,
            'used_count' => 20,
            'created_by' => $this->admin->id,
        ]);

        Livewire::actingAs($this->admin)
            ->test(EditCouponForm::class, ['couponId' => $usedCoupon->id])
            ->set('usageLimit', 10) // En dessous du used_count (20)
            ->call('save')
            ->assertHasErrors(['usageLimit']);
    }

    #[Test]
    public function test_can_change_from_percentage_to_fixed_amount(): void
    {
        Livewire::actingAs($this->admin)
            ->test(EditCouponForm::class, ['couponId' => $this->coupon->id])
            ->set('type', CouponType::FIXED_AMOUNT()->value)
            ->set('value', 750.00)
            ->call('save')
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->coupon->refresh();

        $this->assertEquals('fixed_amount', $this->coupon->type->value);
        $this->assertEquals(750.00, $this->coupon->value);
    }

    #[Test]
    public function test_can_activate_inactive_coupon(): void
    {
        $inactiveCoupon = Coupon::create([
            'code' => 'INACTIVE_COUPON',
            'type' => CouponType::PERCENTAGE()->value,
            'value' => 10.00,
            'status' => CouponStatus::INACTIVE()->value,
            'is_active' => false,
            'usage_limit' => 100,
            'per_user_limit' => 1,
            'used_count' => 0,
            'created_by' => $this->admin->id,
        ]);

        Livewire::actingAs($this->admin)
            ->test(EditCouponForm::class, ['couponId' => $inactiveCoupon->id])
            ->set('status', CouponStatus::ACTIVE()->value)
            ->call('save')
            ->assertRedirect()
            ->assertSessionHas('success');

        $inactiveCoupon->refresh();

        $this->assertEquals('active', $inactiveCoupon->status->value);
        $this->assertTrue($inactiveCoupon->is_active);
    }

    #[Test]
    public function test_form_handles_non_existent_coupon(): void
    {
        $nonExistentId = 99999;

        Livewire::actingAs($this->admin)
            ->test(EditCouponForm::class, ['couponId' => $nonExistentId])
            ->assertStatus(500); // Should throw ModelNotFoundException
    }

    #[Test]
    public function test_date_validation_on_update(): void
    {
        $validFrom = now()->addDays(10)->format('Y-m-d');
        $validUntil = now()->addDays(5)->format('Y-m-d'); // Avant valid_from

        Livewire::actingAs($this->admin)
            ->test(EditCouponForm::class, ['couponId' => $this->coupon->id])
            ->set('validFrom', $validFrom)
            ->set('validUntil', $validUntil)
            ->call('save')
            ->assertHasErrors(['validUntil']);
    }

    #[Test]
    public function test_can_update_per_user_limit(): void
    {
        Livewire::actingAs($this->admin)
            ->test(EditCouponForm::class, ['couponId' => $this->coupon->id])
            ->set('perUserLimit', 5)
            ->call('save')
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->coupon->refresh();
        $this->assertEquals(5, $this->coupon->per_user_limit);
    }

    #[Test]
    public function test_preserves_creation_info_on_update(): void
    {
        $originalCreatedBy = $this->coupon->created_by;
        $originalCreatedAt = $this->coupon->created_at;

        Livewire::actingAs($this->admin)
            ->test(EditCouponForm::class, ['couponId' => $this->coupon->id])
            ->set('value', 30.00)
            ->call('save')
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->coupon->refresh();

        $this->assertEquals($originalCreatedBy, $this->coupon->created_by);
        $this->assertEquals($originalCreatedAt->timestamp, $this->coupon->created_at->timestamp);
        $this->assertEquals(30.00, $this->coupon->value);
    }
}
