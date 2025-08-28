<?php

namespace Tests\Feature;

use App\Enums\CouponStatus;
use App\Enums\CouponType;
use App\Models\Coupon;
use App\Models\Package;
use App\Models\User;
use App\Models\UserSubscription;
use App\Models\Wallet;
use App\Services\CouponService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CouponSystemTest extends TestCase
{
    use RefreshDatabase;

    protected CouponService $couponService;
    protected User $user;
    protected Package $package;

    protected function setUp(): void
    {
        parent::setUp();

        $this->couponService = app(CouponService::class);

        // Créer un utilisateur test
        $this->user = User::factory()->create([
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
        ]);

        // Créer un wallet
        $this->user->wallet()->create([
            'balance' => 10000.00,
            'currency' => 'XAF',
        ]);

        // Créer un package test
        $this->package = Package::factory()->create([
            'name' => 'test_package',
            'display_name' => 'Test Package',
            'price' => 2000.00,
            'is_active' => true,
        ]);
    }

    public function test_can_create_percentage_coupon()
    {
        $coupon = Coupon::create([
            'code' => 'SAVE20',
            'type' => CouponType::PERCENTAGE(),
            'value' => 20.00,
            'status' => CouponStatus::ACTIVE(),
            'usage_limit' => 100,
            'used_count' => 0,
            'created_by' => $this->user->id,
        ]);

        $this->assertDatabaseHas('coupons', [
            'code' => 'SAVE20',
            'type' => 'percentage',
            'value' => 20.00,
        ]);

        $this->assertEquals(20.00, $coupon->value);
        $this->assertTrue($coupon->isValid());
    }

    public function test_can_create_fixed_amount_coupon()
    {
        $coupon = Coupon::create([
            'code' => 'DISCOUNT500',
            'type' => CouponType::FIXED_AMOUNT(),
            'value' => 500.00,
            'status' => CouponStatus::ACTIVE(),
            'usage_limit' => 50,
            'used_count' => 0,
            'created_by' => $this->user->id,
        ]);

        $this->assertDatabaseHas('coupons', [
            'code' => 'DISCOUNT500',
            'type' => 'fixed_amount',
            'value' => 500.00,
        ]);

        $this->assertEquals(500.00, $coupon->value);
    }

    public function test_percentage_coupon_calculates_discount_correctly()
    {
        $coupon = Coupon::create([
            'code' => 'SAVE25',
            'type' => CouponType::PERCENTAGE(),
            'value' => 25.00,
            'status' => CouponStatus::ACTIVE(),
            'usage_limit' => 100,
            'used_count' => 0,
            'created_by' => $this->user->id,
        ]);

        $discount = $coupon->calculateDiscount(2000.00);
        $finalPrice = $coupon->applyDiscount(2000.00);

        $this->assertEquals(500.00, $discount); // 25% of 2000
        $this->assertEquals(1500.00, $finalPrice); // 2000 - 500
    }

    public function test_fixed_amount_coupon_calculates_discount_correctly()
    {
        $coupon = Coupon::create([
            'code' => 'FIXED1000',
            'type' => CouponType::FIXED_AMOUNT(),
            'value' => 1000.00,
            'status' => CouponStatus::ACTIVE(),
            'usage_limit' => 100,
            'used_count' => 0,
            'created_by' => $this->user->id,
        ]);

        $discount = $coupon->calculateDiscount(2000.00);
        $finalPrice = $coupon->applyDiscount(2000.00);

        $this->assertEquals(1000.00, $discount);
        $this->assertEquals(1000.00, $finalPrice); // 2000 - 1000
    }

    public function test_fixed_amount_coupon_cannot_exceed_total_price()
    {
        $coupon = Coupon::create([
            'code' => 'FIXED3000',
            'type' => CouponType::FIXED_AMOUNT(),
            'value' => 3000.00,
            'status' => CouponStatus::ACTIVE(),
            'usage_limit' => 100,
            'used_count' => 0,
            'created_by' => $this->user->id,
        ]);

        $discount = $coupon->calculateDiscount(2000.00);
        $finalPrice = $coupon->applyDiscount(2000.00);

        $this->assertEquals(2000.00, $discount); // Limited to the total price
        $this->assertEquals(0.00, $finalPrice); // Cannot go negative
    }

    public function test_coupon_service_validates_valid_coupon()
    {
        $coupon = Coupon::create([
            'code' => 'VALID30',
            'type' => CouponType::PERCENTAGE(),
            'value' => 30.00,
            'status' => CouponStatus::ACTIVE(),
            'usage_limit' => 100,
            'used_count' => 0,
            'created_by' => $this->user->id,
        ]);

        $validation = $this->couponService->validateCoupon('VALID30', $this->user, 2000.00);

        $this->assertTrue($validation['valid']);
        $this->assertEquals('Code coupon valide.', $validation['message']);
        $this->assertEquals(600.00, $validation['discount_amount']); // 30% of 2000
        $this->assertEquals(1400.00, $validation['final_price']); // 2000 - 600
        $this->assertEquals(600.00, $validation['savings']);
    }

    public function test_coupon_service_rejects_invalid_code()
    {
        $validation = $this->couponService->validateCoupon('INVALID', $this->user, 2000.00);

        $this->assertFalse($validation['valid']);
        $this->assertEquals('Code coupon invalide.', $validation['message']);
    }

    public function test_coupon_service_rejects_expired_coupon()
    {
        $coupon = Coupon::create([
            'code' => 'EXPIRED',
            'type' => CouponType::PERCENTAGE(),
            'value' => 20.00,
            'status' => CouponStatus::EXPIRED(),
            'usage_limit' => 100,
            'used_count' => 0,
            'created_by' => $this->user->id,
        ]);

        $validation = $this->couponService->validateCoupon('EXPIRED', $this->user, 2000.00);

        $this->assertFalse($validation['valid']);
        $this->assertEquals('Ce code coupon a expiré.', $validation['message']);
    }

    public function test_coupon_service_rejects_fully_used_coupon()
    {
        $coupon = Coupon::create([
            'code' => 'FULLUSED',
            'type' => CouponType::PERCENTAGE(),
            'value' => 20.00,
            'status' => CouponStatus::ACTIVE(),
            'usage_limit' => 1,
            'used_count' => 1, // Fully used
            'created_by' => $this->user->id,
        ]);

        $validation = $this->couponService->validateCoupon('FULLUSED', $this->user, 2000.00);

        $this->assertFalse($validation['valid']);
        $this->assertEquals('Ce code coupon a atteint sa limite d\'utilisation.', $validation['message']);
    }

    public function test_coupon_service_applies_coupon_correctly()
    {
        $coupon = Coupon::create([
            'code' => 'APPLY20',
            'type' => CouponType::PERCENTAGE(),
            'value' => 20.00,
            'status' => CouponStatus::ACTIVE(),
            'usage_limit' => 100,
            'used_count' => 0,
            'created_by' => $this->user->id,
        ]);

        // Créer une souscription
        $subscription = UserSubscription::factory()->create([
            'user_id' => $this->user->id,
            'package_id' => $this->package->id,
            'amount_paid' => 1600.00, // 2000 - 400 (20%)
        ]);

        $this->couponService->applyCoupon($coupon, $this->user, $subscription, 2000.00);

        // Vérifier que l'utilisation du coupon est enregistrée
        $this->assertDatabaseHas('coupon_usages', [
            'coupon_id' => $coupon->id,
            'user_id' => $this->user->id,
            'user_subscription_id' => $subscription->id,
            'original_price' => 2000.00,
            'discount_amount' => 400.00,
            'final_price' => 1600.00,
        ]);

        // Vérifier que le compteur d'utilisation est mis à jour
        $coupon->refresh();
        $this->assertEquals(1, $coupon->used_count);
    }

    public function test_coupon_becomes_used_when_limit_reached()
    {
        $coupon = Coupon::create([
            'code' => 'LIMIT1',
            'type' => CouponType::PERCENTAGE(),
            'value' => 20.00,
            'status' => CouponStatus::ACTIVE(),
            'usage_limit' => 1, // Limite de 1
            'used_count' => 0,
            'created_by' => $this->user->id,
        ]);

        // Utiliser le coupon
        $coupon->markAsUsed();

        $coupon->refresh();
        $this->assertEquals(1, $coupon->used_count);
        $this->assertEquals(CouponStatus::USED(), $coupon->status);
    }

    public function test_coupon_can_generate_unique_code()
    {
        $code1 = Coupon::generateUniqueCode();
        $code2 = Coupon::generateUniqueCode();

        $this->assertNotEquals($code1, $code2);
        $this->assertEquals(8, strlen($code1));
        $this->assertEquals(8, strlen($code2));
        $this->assertTrue(ctype_alnum($code1));
        $this->assertTrue(ctype_alnum($code2));
    }
}
