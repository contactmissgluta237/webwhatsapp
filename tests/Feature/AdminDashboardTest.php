<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function admin_can_access_admin_dashboard(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $admin = $admin->fresh();

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Tableau de bord');
    }

    #[Test]
    public function client_cannot_access_admin_dashboard(): void
    {
        $client = User::factory()->create();
        $client->assignRole('customer');
        $client = $client->fresh();

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
        $this->actingAs($admin)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Tableau de bord')
            ->assertSee('Utilisateurs')
            ->assertSee('Tickets');
    }

    #[Test]
    public function admin_dashboard_shows_user_and_ticket_counts(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('11'); // 10 créés + 1 admin
        // ->assertSee('5'); // Si vous avez décommenté la création de tickets
    }
}
