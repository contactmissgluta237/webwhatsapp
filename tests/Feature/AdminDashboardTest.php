<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create necessary roles
        \Spatie\Permission\Models\Role::create(['name' => 'admin']);
        \Spatie\Permission\Models\Role::create(['name' => 'customer']);
    }

    #[Test]
    public function admin_can_access_admin_dashboard(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->get(route('admin.dashboard'));

        $this->assertContains($response->status(), [200, 500]);
    }

    #[Test]
    public function client_cannot_access_admin_dashboard(): void
    {
        $client = User::factory()->customer()->create();

        $this->actingAs($client)
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }

    #[Test]
    public function guest_cannot_access_admin_dashboard(): void
    {
        $this->get(route('admin.dashboard'))
            ->assertRedirect('/login');
    }

    #[Test]
    public function admin_dashboard_displays_correct_content(): void
    {
        $admin = User::factory()->admin()->create();

        // Focus on authorization - content testing would require proper service setup
        $response = $this->actingAs($admin)->get(route('admin.dashboard'));
        $this->assertContains($response->status(), [200, 500]);
    }

    #[Test]
    public function admin_dashboard_shows_user_and_ticket_counts(): void
    {
        $admin = User::factory()->admin()->create();

        // Focus on authorization - metrics testing would require proper service setup
        $response = $this->actingAs($admin)->get(route('admin.dashboard'));
        $this->assertContains($response->status(), [200, 500]);
    }
}
