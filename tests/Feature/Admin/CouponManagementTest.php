<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Enums\CouponStatus;
use App\Enums\CouponType;
use App\Models\Coupon;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CouponManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        // Create necessary roles
        \Spatie\Permission\Models\Role::create(['name' => 'admin']);
        \Spatie\Permission\Models\Role::create(['name' => 'customer']);

        $this->admin = User::factory()->admin()->create();
        $this->customer = User::factory()->customer()->create();
    }

    #[Test]
    public function test_admin_can_view_coupons_page(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.coupons.index'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.coupons.index');
        $response->assertSee('Gestion des Coupons');
        $response->assertSee('Nouveau Coupon');
    }

    #[Test]
    public function test_coupons_are_displayed_in_datatable(): void
    {
        // Créer des coupons de test
        $percentageCoupon = Coupon::create([
            'code' => 'SAVE20',
            'type' => CouponType::PERCENTAGE()->value,
            'value' => 20.00,
            'status' => CouponStatus::ACTIVE()->value,
            'is_active' => true,
            'usage_limit' => 100,
            'per_user_limit' => 1,
            'used_count' => 5,
            'created_by' => $this->admin->id,
        ]);

        $fixedAmountCoupon = Coupon::create([
            'code' => 'DISCOUNT500',
            'type' => CouponType::FIXED_AMOUNT()->value,
            'value' => 500.00,
            'status' => CouponStatus::ACTIVE()->value,
            'is_active' => true,
            'usage_limit' => 50,
            'per_user_limit' => 1,
            'used_count' => 2,
            'created_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.coupons.index'));

        $response->assertStatus(200);

        // Vérifier que les coupons sont affichés
        $response->assertSee('SAVE20');
        $response->assertSee('DISCOUNT500');
        $response->assertSee('Pourcentage');
        $response->assertSee('Montant Fixe');
        $response->assertSee('20%');
        $response->assertSee('500 XAF');
        $response->assertSee('5/100');
        $response->assertSee('2/50');
    }

    #[Test]
    public function test_coupon_creation_modal_opens(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.coupons.index'));

        $response->assertStatus(200);
        $response->assertSee('wire:click="openCreateModal"');
        $response->assertSee('Créer un nouveau coupon');
    }

    #[Test]
    public function test_coupon_edit_route_works(): void
    {
        $coupon = Coupon::create([
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

        $response = $this->actingAs($this->admin)
            ->get(route('admin.coupons.edit', $coupon->id));

        $response->assertStatus(200);
    }

    #[Test]
    public function test_coupon_status_can_be_toggled(): void
    {
        $coupon = Coupon::create([
            'code' => 'TOGGLE_TEST',
            'type' => CouponType::PERCENTAGE()->value,
            'value' => 10.00,
            'status' => CouponStatus::ACTIVE()->value,
            'is_active' => true,
            'usage_limit' => 100,
            'per_user_limit' => 1,
            'used_count' => 0,
            'created_by' => $this->admin->id,
        ]);

        // Désactiver le coupon
        $response = $this->actingAs($this->admin)
            ->post(route('admin.coupons.toggle-status', $coupon->id), [
                'action' => 'deactivate',
            ]);

        $response->assertRedirect();
        $coupon->refresh();
        $this->assertEquals(CouponStatus::INACTIVE()->value, $coupon->status->value);

        // Réactiver le coupon
        $response = $this->actingAs($this->admin)
            ->post(route('admin.coupons.toggle-status', $coupon->id), [
                'action' => 'activate',
            ]);

        $response->assertRedirect();
        $coupon->refresh();
        $this->assertEquals(CouponStatus::ACTIVE()->value, $coupon->status->value);
    }

    #[Test]
    public function test_unused_coupon_can_be_deleted(): void
    {
        $coupon = Coupon::create([
            'code' => 'DELETE_TEST',
            'type' => CouponType::PERCENTAGE()->value,
            'value' => 5.00,
            'status' => CouponStatus::ACTIVE()->value,
            'is_active' => true,
            'usage_limit' => 100,
            'per_user_limit' => 1,
            'used_count' => 0,
            'created_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->delete(route('admin.coupons.delete', $coupon->id));

        $response->assertRedirect();
        $this->assertDatabaseMissing('coupons', ['id' => $coupon->id]);
    }

    #[Test]
    public function test_used_coupon_cannot_be_deleted(): void
    {
        $coupon = Coupon::create([
            'code' => 'USED_DELETE_TEST',
            'type' => CouponType::PERCENTAGE()->value,
            'value' => 5.00,
            'status' => CouponStatus::ACTIVE()->value,
            'is_active' => true,
            'usage_limit' => 100,
            'per_user_limit' => 1,
            'used_count' => 1, // Coupon déjà utilisé
            'created_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->delete(route('admin.coupons.delete', $coupon->id));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('coupons', ['id' => $coupon->id]);
    }

    #[Test]
    public function test_coupon_actions_are_displayed_correctly(): void
    {
        $activeCoupon = Coupon::create([
            'code' => 'ACTIVE_ACTIONS',
            'type' => CouponType::PERCENTAGE()->value,
            'value' => 10.00,
            'status' => CouponStatus::ACTIVE()->value,
            'is_active' => true,
            'usage_limit' => 100,
            'per_user_limit' => 1,
            'used_count' => 0,
            'created_by' => $this->admin->id,
        ]);

        $inactiveCoupon = Coupon::create([
            'code' => 'INACTIVE_ACTIONS',
            'type' => CouponType::PERCENTAGE()->value,
            'value' => 10.00,
            'status' => CouponStatus::INACTIVE()->value,
            'is_active' => false,
            'usage_limit' => 100,
            'per_user_limit' => 1,
            'used_count' => 0,
            'created_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.coupons.index'));

        $response->assertStatus(200);

        // Vérifier les actions pour le coupon actif
        $response->assertSee('la la-copy'); // Copier
        $response->assertSee('la la-edit'); // Modifier
        $response->assertSee('la la-pause'); // Désactiver
        $response->assertSee('la la-trash'); // Supprimer

        // Vérifier les actions pour le coupon inactif
        $response->assertSee('la la-play'); // Activer
    }

    #[Test]
    public function test_coupon_usage_progress_is_displayed(): void
    {
        $coupon = Coupon::create([
            'code' => 'PROGRESS_TEST',
            'type' => CouponType::PERCENTAGE()->value,
            'value' => 15.00,
            'status' => CouponStatus::ACTIVE()->value,
            'is_active' => true,
            'usage_limit' => 10,
            'per_user_limit' => 1,
            'used_count' => 3,
            'created_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.coupons.index'));

        $response->assertStatus(200);
        $response->assertSee('3/10'); // Usage count
        $response->assertSee('progress-bar'); // Progress bar element
    }

    #[Test]
    public function test_empty_state_when_no_coupons(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.coupons.index'));

        $response->assertStatus(200);
        $response->assertSee('Aucun coupon trouvé');
        $response->assertSee('la la-inbox');
    }

    #[Test]
    public function test_coupon_filters_work(): void
    {
        // Créer des coupons avec différents statuts et types
        Coupon::create([
            'code' => 'ACTIVE_PERCENTAGE',
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
            'code' => 'INACTIVE_FIXED',
            'type' => CouponType::FIXED_AMOUNT()->value,
            'value' => 500.00,
            'status' => CouponStatus::INACTIVE()->value,
            'is_active' => false,
            'usage_limit' => 50,
            'per_user_limit' => 1,
            'used_count' => 0,
            'created_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.coupons.index'));

        $response->assertStatus(200);

        // Vérifier la présence des filtres
        $response->assertSee('wire:model.live="search"');
        $response->assertSee('wire:model.live="filterStatus"');
        $response->assertSee('wire:model.live="filterType"');
        $response->assertSee('Tous les statuts');
        $response->assertSee('Tous les types');
    }

    #[Test]
    public function test_non_admin_cannot_access_coupons_page(): void
    {
        $this->actingAs($this->customer)
            ->get(route('admin.coupons.index'))
            ->assertForbidden();
    }

    #[Test]
    public function test_guest_cannot_access_coupons_page(): void
    {
        $this->get(route('admin.coupons.index'))
            ->assertRedirect('/login');
    }

    #[Test]
    public function test_coupon_copy_functionality_exists(): void
    {
        $coupon = Coupon::create([
            'code' => 'COPY_TEST_123',
            'type' => CouponType::PERCENTAGE()->value,
            'value' => 25.00,
            'status' => CouponStatus::ACTIVE()->value,
            'is_active' => true,
            'usage_limit' => 100,
            'per_user_limit' => 1,
            'used_count' => 0,
            'created_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.coupons.index'));

        $response->assertStatus(200);
        $response->assertSee('copyCoupon(\'COPY_TEST_123\')');
        $response->assertSee('function copyCoupon(code)');
        $response->assertSee('navigator.clipboard.writeText(code)');
    }

    #[Test]
    public function test_coupon_status_badges_are_displayed(): void
    {
        $activeCoupon = Coupon::create([
            'code' => 'BADGE_ACTIVE',
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
            'code' => 'BADGE_EXPIRED',
            'type' => CouponType::PERCENTAGE()->value,
            'value' => 10.00,
            'status' => CouponStatus::EXPIRED()->value,
            'is_active' => false,
            'usage_limit' => 100,
            'per_user_limit' => 1,
            'used_count' => 0,
            'created_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.coupons.index'));

        $response->assertStatus(200);
        $response->assertSee('bg-success'); // Badge vert pour actif
        $response->assertSee('bg-warning'); // Badge orange pour expiré
    }

    #[Test]
    public function test_coupon_validity_dates_are_displayed(): void
    {
        $coupon = Coupon::create([
            'code' => 'DATE_TEST',
            'type' => CouponType::PERCENTAGE()->value,
            'value' => 10.00,
            'status' => CouponStatus::ACTIVE()->value,
            'is_active' => true,
            'usage_limit' => 100,
            'per_user_limit' => 1,
            'used_count' => 0,
            'valid_from' => now()->subDays(5),
            'valid_until' => now()->addDays(10),
            'created_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.coupons.index'));

        $response->assertStatus(200);
        $response->assertSee('Du '.$coupon->valid_from->format('d/m/Y'));
        $response->assertSee('Au '.$coupon->valid_until->format('d/m/Y'));
    }

    #[Test]
    public function test_coupon_creator_is_displayed(): void
    {
        $coupon = Coupon::create([
            'code' => 'CREATOR_TEST',
            'type' => CouponType::PERCENTAGE()->value,
            'value' => 10.00,
            'status' => CouponStatus::ACTIVE()->value,
            'is_active' => true,
            'usage_limit' => 100,
            'per_user_limit' => 1,
            'used_count' => 0,
            'created_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.coupons.index'));

        $response->assertStatus(200);
        $response->assertSee($this->admin->full_name);
    }
}
