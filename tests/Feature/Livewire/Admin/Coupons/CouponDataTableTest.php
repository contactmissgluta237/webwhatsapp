<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire\Admin\Coupons;

use App\Enums\CouponStatus;
use App\Enums\CouponType;
use App\Livewire\Admin\Coupons\CouponDataTable;
use App\Models\Coupon;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CouponDataTableTest extends TestCase
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
            ->test(CouponDataTable::class)
            ->assertStatus(200)
            ->assertSee('Aucun coupon trouvé');
    }

    #[Test]
    public function test_coupons_are_displayed_in_table(): void
    {
        $coupon = Coupon::create([
            'code' => 'TEST_DISPLAY',
            'type' => CouponType::PERCENTAGE()->value,
            'value' => 15.00,
            'status' => CouponStatus::ACTIVE()->value,
            'is_active' => true,
            'usage_limit' => 100,
            'per_user_limit' => 1,
            'used_count' => 5,
            'created_by' => $this->admin->id,
        ]);

        Livewire::actingAs($this->admin)
            ->test(CouponDataTable::class)
            ->assertSee('TEST_DISPLAY')
            ->assertSee('15%')
            ->assertSee('5/100')
            ->assertSee('Pourcentage');
    }

    #[Test]
    public function test_search_functionality_works(): void
    {
        Coupon::create([
            'code' => 'SEARCHABLE_COUPON',
            'type' => CouponType::PERCENTAGE()->value,
            'value' => 10.00,
            'status' => CouponStatus::ACTIVE()->value,
            'is_active' => true,
            'usage_limit' => 100,
            'per_user_limit' => 1,
            'used_count' => 0,
            'created_by' => $this->admin->id,
        ]);

        Coupon::create([
            'code' => 'ANOTHER_COUPON',
            'type' => CouponType::FIXED_AMOUNT()->value,
            'value' => 500.00,
            'status' => CouponStatus::ACTIVE()->value,
            'is_active' => true,
            'usage_limit' => 50,
            'per_user_limit' => 1,
            'used_count' => 0,
            'created_by' => $this->admin->id,
        ]);

        Livewire::actingAs($this->admin)
            ->test(CouponDataTable::class)
            ->set('search', 'SEARCHABLE')
            ->assertSee('SEARCHABLE_COUPON')
            ->assertDontSee('ANOTHER_COUPON');
    }

    #[Test]
    public function test_status_filter_works(): void
    {
        Coupon::create([
            'code' => 'ACTIVE_COUPON',
            'type' => CouponType::PERCENTAGE()->value,
            'value' => 10.00,
            'status' => CouponStatus::ACTIVE()->value,
            'is_active' => true,
            'usage_limit' => 100,
            'per_user_limit' => 1,
            'used_count' => 0,
            'created_by' => $this->admin->id,
        ]);

        Coupon::create([
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
            ->test(CouponDataTable::class)
            ->set('filterStatus', CouponStatus::ACTIVE()->value)
            ->assertSee('ACTIVE_COUPON')
            ->assertDontSee('INACTIVE_COUPON');
    }

    #[Test]
    public function test_type_filter_works(): void
    {
        Coupon::create([
            'code' => 'PERCENTAGE_COUPON',
            'type' => CouponType::PERCENTAGE()->value,
            'value' => 20.00,
            'status' => CouponStatus::ACTIVE()->value,
            'is_active' => true,
            'usage_limit' => 100,
            'per_user_limit' => 1,
            'used_count' => 0,
            'created_by' => $this->admin->id,
        ]);

        Coupon::create([
            'code' => 'FIXED_COUPON',
            'type' => CouponType::FIXED_AMOUNT()->value,
            'value' => 1000.00,
            'status' => CouponStatus::ACTIVE()->value,
            'is_active' => true,
            'usage_limit' => 50,
            'per_user_limit' => 1,
            'used_count' => 0,
            'created_by' => $this->admin->id,
        ]);

        Livewire::actingAs($this->admin)
            ->test(CouponDataTable::class)
            ->set('filterType', CouponType::PERCENTAGE()->value)
            ->assertSee('PERCENTAGE_COUPON')
            ->assertDontSee('FIXED_COUPON');
    }

    #[Test]
    public function test_delete_coupon_removes_unused_coupon(): void
    {
        $coupon = Coupon::create([
            'code' => 'DELETE_ME',
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
            ->test(CouponDataTable::class)
            ->call('deleteCoupon', $coupon->id)
            ->assertSessionHas('success', 'Coupon supprimé avec succès.');

        $this->assertDatabaseMissing('coupons', ['id' => $coupon->id]);
    }

    #[Test]
    public function test_delete_coupon_prevents_deleting_used_coupon(): void
    {
        $coupon = Coupon::create([
            'code' => 'CANT_DELETE',
            'type' => CouponType::PERCENTAGE()->value,
            'value' => 10.00,
            'status' => CouponStatus::ACTIVE()->value,
            'is_active' => true,
            'usage_limit' => 100,
            'per_user_limit' => 1,
            'used_count' => 5, // Déjà utilisé
            'created_by' => $this->admin->id,
        ]);

        Livewire::actingAs($this->admin)
            ->test(CouponDataTable::class)
            ->call('deleteCoupon', $coupon->id)
            ->assertSessionHas('error', 'Impossible de supprimer un coupon déjà utilisé.');

        $this->assertDatabaseHas('coupons', ['id' => $coupon->id]);
    }

    #[Test]
    public function test_filters_can_be_reset(): void
    {
        Livewire::actingAs($this->admin)
            ->test(CouponDataTable::class)
            ->set('search', 'test')
            ->set('filterStatus', 'active')
            ->set('filterType', 'percentage')
            ->assertSet('search', 'test')
            ->assertSet('filterStatus', 'active')
            ->assertSet('filterType', 'percentage')
            ->call('resetFilters')
            ->assertSet('search', '')
            ->assertSet('filterStatus', '')
            ->assertSet('filterType', '');
    }

    #[Test]
    public function test_pagination_works_with_many_coupons(): void
    {
        // Créer plus de coupons que la limite de pagination
        for ($i = 1; $i <= 25; $i++) {
            Coupon::create([
                'code' => "COUPON_{$i}",
                'type' => CouponType::PERCENTAGE()->value,
                'value' => 10.00,
                'status' => CouponStatus::ACTIVE()->value,
                'is_active' => true,
                'usage_limit' => 100,
                'per_user_limit' => 1,
                'used_count' => 0,
                'created_by' => $this->admin->id,
            ]);
        }

        Livewire::actingAs($this->admin)
            ->test(CouponDataTable::class)
            ->assertSee('COUPON_1')
            ->assertSee('COUPON_15')
            ->assertDontSee('COUPON_25'); // Pagination devrait cacher ce coupon
    }

    #[Test]
    public function test_coupon_usage_progress_calculation(): void
    {
        $coupon = Coupon::create([
            'code' => 'PROGRESS_TEST',
            'type' => CouponType::PERCENTAGE()->value,
            'value' => 10.00,
            'status' => CouponStatus::ACTIVE()->value,
            'is_active' => true,
            'usage_limit' => 20,
            'per_user_limit' => 1,
            'used_count' => 5,
            'created_by' => $this->admin->id,
        ]);

        Livewire::actingAs($this->admin)
            ->test(CouponDataTable::class)
            ->assertSee('5/20')
            ->assertSee('25%'); // 5/20 * 100 = 25%
    }

    #[Test]
    public function test_coupon_type_display_formatting(): void
    {
        $percentageCoupon = Coupon::create([
            'code' => 'PERCENT_FORMAT',
            'type' => CouponType::PERCENTAGE()->value,
            'value' => 15.50,
            'status' => CouponStatus::ACTIVE()->value,
            'is_active' => true,
            'usage_limit' => 100,
            'per_user_limit' => 1,
            'used_count' => 0,
            'created_by' => $this->admin->id,
        ]);

        $fixedCoupon = Coupon::create([
            'code' => 'FIXED_FORMAT',
            'type' => CouponType::FIXED_AMOUNT()->value,
            'value' => 2500.00,
            'status' => CouponStatus::ACTIVE()->value,
            'is_active' => true,
            'usage_limit' => 50,
            'per_user_limit' => 1,
            'used_count' => 0,
            'created_by' => $this->admin->id,
        ]);

        Livewire::actingAs($this->admin)
            ->test(CouponDataTable::class)
            ->assertSee('15.5%') // Percentage format
            ->assertSee('2 500 USD'); // Fixed amount format with thousands separator
    }

    #[Test]
    public function test_coupon_status_badge_colors(): void
    {
        $activeCoupon = Coupon::create([
            'code' => 'ACTIVE_BADGE',
            'type' => CouponType::PERCENTAGE()->value,
            'value' => 10.00,
            'status' => CouponStatus::ACTIVE()->value,
            'is_active' => true,
            'usage_limit' => 100,
            'per_user_limit' => 1,
            'used_count' => 0,
            'created_by' => $this->admin->id,
        ]);

        $expiredCoupon = Coupon::create([
            'code' => 'EXPIRED_BADGE',
            'type' => CouponType::PERCENTAGE()->value,
            'value' => 10.00,
            'status' => CouponStatus::EXPIRED()->value,
            'is_active' => false,
            'usage_limit' => 100,
            'per_user_limit' => 1,
            'used_count' => 0,
            'created_by' => $this->admin->id,
        ]);

        Livewire::actingAs($this->admin)
            ->test(CouponDataTable::class)
            ->assertSeeHtml('bg-success') // Active badge
            ->assertSeeHtml('bg-warning'); // Expired badge
    }
}
