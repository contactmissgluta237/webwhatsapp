<?php

declare(strict_types=1);

namespace Tests\Feature\General;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class GeneralControllersTest extends TestCase
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
    public function auth_check_controller_redirects_authenticated_user(): void
    {
        $this->actingAs($this->user)
            ->get('/')
            ->assertRedirect(); // Should redirect to appropriate dashboard based on role
    }

    #[Test]
    public function auth_check_controller_shows_welcome_to_guest(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertViewIs('welcome'); // Assuming there's a welcome view for guests
    }

    #[Test]
    public function push_notification_diagnostic_requires_authentication(): void
    {
        $this->get(route('push.diagnostic'))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function authenticated_user_can_access_push_diagnostic(): void
    {
        $this->actingAs($this->user)
            ->get(route('push.diagnostic'))
            ->assertOk()
            ->assertViewIs('push.diagnostic');
    }
}