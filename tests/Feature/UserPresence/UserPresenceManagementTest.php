<?php

declare(strict_types=1);

namespace Tests\Feature\UserPresence;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class UserPresenceManagementTest extends TestCase
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
    public function authenticated_user_can_send_heartbeat(): void
    {
        $this->actingAs($this->user)
            ->post(route('user.heartbeat'))
            ->assertOk()
            ->assertJson([
                'message' => 'Heartbeat recorded.',
            ])
            ->assertJsonStructure([
                'message',
                'timestamp',
            ]);
    }

    #[Test]
    public function authenticated_user_can_mark_offline(): void
    {
        $this->actingAs($this->user)
            ->post(route('user.offline'))
            ->assertOk()
            ->assertJson([
                'message' => 'User marked as offline.',
            ]);
    }

    #[Test]
    public function authenticated_user_can_get_status(): void
    {
        $this->actingAs($this->user)
            ->get(route('user.status'))
            ->assertOk()
            ->assertJsonStructure([
                'online',
                'last_seen',
            ]);
    }

    #[Test]
    public function guest_cannot_access_user_presence_endpoints(): void
    {
        $this->post(route('user.heartbeat'))
            ->assertRedirect(route('login'));

        $this->post(route('user.offline'))
            ->assertRedirect(route('login'));

        $this->get(route('user.status'))
            ->assertRedirect(route('login'));
    }
}