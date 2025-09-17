<?php

declare(strict_types=1);

namespace Tests\Feature\Test;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class TestEndpointsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Créer les rôles nécessaires
        \Spatie\Permission\Models\Role::create(['name' => 'customer']);
        \Spatie\Permission\Models\Role::create(['name' => 'admin']);

        // Créer un pays pour éviter les erreurs de validation
        \Illuminate\Support\Facades\DB::table('countries')->insert([
            'id' => 1,
            'name' => 'Cameroon',
            'code' => 'CM',
            'phone_code' => '+237',
            'flag_emoji' => '🇨🇲',
            'is_active' => true,
            'sort_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->user = User::factory()->create();
        $this->user->assignRole('customer');
        $this->user = $this->user->fresh();
    }

    #[Test]
    public function authenticated_user_can_access_test_notification(): void
    {
        $this->actingAs($this->user)
            ->get('/test/notification')
            ->assertOk()
            ->assertViewIs('test.notification');
    }

    #[Test]
    public function guest_cannot_access_test_notification(): void
    {
        $this->get('/test/notification')
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function test_payment_recharge_endpoint_works(): void
    {
        $amount = 5000;

        $this->get(route('test.payment.execute', ['action' => 'recharge', 'amount' => $amount]))
            ->assertOk()
            ->assertViewIs('test.payment.execute')
            ->assertViewHas('action', 'recharge')
            ->assertViewHas('amount', $amount);
    }

    #[Test]
    public function test_payment_withdraw_endpoint_works(): void
    {
        $amount = 2500;

        $this->get(route('test.payment.execute', ['action' => 'withdraw', 'amount' => $amount]))
            ->assertOk()
            ->assertViewIs('test.payment.execute')
            ->assertViewHas('action', 'withdraw')
            ->assertViewHas('amount', $amount);
    }

    #[Test]
    public function test_payment_balance_endpoint_works(): void
    {
        $this->get(route('test.payment.balance'))
            ->assertOk()
            ->assertViewIs('test.payment.balance');
    }

    #[Test]
    public function test_payment_status_endpoint_works(): void
    {
        $transactionId = 'tx_123456789';

        $this->get(route('test.payment.status', ['transactionId' => $transactionId]))
            ->assertOk()
            ->assertViewIs('test.payment.status')
            ->assertViewHas('transactionId', $transactionId);
    }

    #[Test]
    public function test_payment_execute_validates_action(): void
    {
        $this->get('/test/payment/invalid/5000')
            ->assertNotFound();
    }

    #[Test]
    public function test_payment_execute_validates_amount(): void
    {
        $this->get('/test/payment/recharge/invalid')
            ->assertNotFound();
    }
}