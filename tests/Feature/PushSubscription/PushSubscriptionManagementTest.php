<?php

declare(strict_types=1);

namespace Tests\Feature\PushSubscription;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class PushSubscriptionManagementTest extends TestCase
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
    public function authenticated_user_can_subscribe_to_push_notifications(): void
    {
        $subscriptionData = [
            'endpoint' => 'https://example.com/push/endpoint',
            'publicKey' => 'test-public-key',
            'authToken' => 'test-auth-token',
        ];

        $this->actingAs($this->user)
            ->post(route('push.subscribe'), $subscriptionData)
            ->assertOk()
            ->assertJson([
                'message' => 'Push subscription created successfully.',
            ]);
    }

    #[Test]
    public function authenticated_user_can_unsubscribe_from_push_notifications(): void
    {
        $this->actingAs($this->user)
            ->delete(route('push.unsubscribe'))
            ->assertOk()
            ->assertJson([
                'message' => 'Push subscription deleted successfully.',
            ]);
    }

    #[Test]
    public function push_subscription_requires_valid_data(): void
    {
        $this->actingAs($this->user)
            ->post(route('push.subscribe'), [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['endpoint', 'publicKey', 'authToken']);
    }

    #[Test]
    public function authenticated_user_can_use_alternative_push_subscription_routes(): void
    {
        $subscriptionData = [
            'endpoint' => 'https://example.com/push/endpoint',
            'publicKey' => 'test-public-key',
            'authToken' => 'test-auth-token',
        ];

        // Test alternative subscribe route
        $this->actingAs($this->user)
            ->post('/push-subscriptions', $subscriptionData)
            ->assertOk();

        // Test alternative unsubscribe route
        $this->actingAs($this->user)
            ->delete('/push-subscriptions')
            ->assertOk();
    }

    #[Test]
    public function guest_cannot_access_push_subscription_endpoints(): void
    {
        $this->post(route('push.subscribe'))
            ->assertRedirect(route('login'));

        $this->delete(route('push.unsubscribe'))
            ->assertRedirect(route('login'));

        $this->post('/push-subscriptions')
            ->assertRedirect(route('login'));

        $this->delete('/push-subscriptions')
            ->assertRedirect(route('login'));
    }
}