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

        // Mock AdminDashboardMetricsService to avoid database dependencies
        $this->app->bind(\App\Services\AdminDashboardMetricsService::class, function () {
            $mock = \Mockery::mock(\App\Services\AdminDashboardMetricsService::class);
            $mock->shouldReceive('getMetrics')->andReturn(
                new \App\DTOs\Dashboard\AdminDashboardMetricsDTO(
                    registeredUsers: 10,
                    totalWithdrawals: 1000.0,
                    totalRecharges: 2000.0,
                    companyProfit: 500.0,
                    period: new \App\DTOs\Dashboard\PeriodDTO(
                        start: '2024-01-01',
                        end: '2024-01-31'
                    )
                )
            );
            $mock->shouldReceive('getSystemAccountsBalance')->andReturn(collect([
                (object) ['type' => 'Orange Money', 'balance' => 1000.0, 'icon' => 'fa-wallet', 'badge' => 'success'],
                (object) ['type' => 'MTN Mobile Money', 'balance' => 2000.0, 'icon' => 'fa-money', 'badge' => 'info'],
            ]));

            return $mock;
        });
    }

    #[Test]
    public function admin_can_access_admin_dashboard(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Tableau de bord');
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
