<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ApiEndpointsTest extends TestCase
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
    public function health_check_endpoint_returns_success(): void
    {
        $this->get('/api/health')
            ->assertOk()
            ->assertJson([
                '_metadata' => [
                    'success' => true,
                    'message' => 'API is running',
                ],
                'data' => [
                    'status' => 'healthy',
                ],
            ])
            ->assertJsonStructure([
                '_metadata' => ['success', 'message'],
                'data' => ['status', 'timestamp'],
            ]);
    }

    #[Test]
    public function authenticated_user_can_access_user_endpoint(): void
    {
        $token = $this->user->createToken('test-token')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->get('/api/user')
            ->assertOk()
            ->assertJson([
                'id' => $this->user->id,
                'email' => $this->user->email,
            ]);
    }

    #[Test]
    public function guest_cannot_access_protected_user_endpoint(): void
    {
        $this->get('/api/user')
            ->assertUnauthorized();
    }

    #[Test]
    public function invalid_token_cannot_access_protected_user_endpoint(): void
    {
        $this->withHeader('Authorization', 'Bearer invalid-token')
            ->get('/api/user')
            ->assertUnauthorized();
    }
}