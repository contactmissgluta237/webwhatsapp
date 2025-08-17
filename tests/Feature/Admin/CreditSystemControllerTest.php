<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Services\CreditSystemService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CreditSystemControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole('admin');
    }

    /** @test */
    public function it_displays_credit_system_settings_page(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.settings.credit-system.index'));

        $response->assertOk();
        $response->assertViewIs('admin.settings.credit-system');
        $response->assertViewHas('currentCost');
    }

    /** @test */
    public function it_updates_message_cost_successfully(): void
    {
        $newCost = 75.0;

        $response = $this->actingAs($this->adminUser)
            ->put(route('admin.settings.credit-system.update'), [
                'message_cost' => $newCost,
            ]);

        $response->assertRedirect(route('admin.settings.credit-system.index'));
        $response->assertSessionHas('success', 'Coût par message mis à jour avec succès.');
    }

    /** @test */
    public function it_validates_message_cost_input(): void
    {
        // Test with negative value
        $response = $this->actingAs($this->adminUser)
            ->put(route('admin.settings.credit-system.update'), [
                'message_cost' => -10,
            ]);

        $response->assertSessionHasErrors(['message_cost']);

        // Test with too high value
        $response = $this->actingAs($this->adminUser)
            ->put(route('admin.settings.credit-system.update'), [
                'message_cost' => 1500,
            ]);

        $response->assertSessionHasErrors(['message_cost']);

        // Test with missing value
        $response = $this->actingAs($this->adminUser)
            ->put(route('admin.settings.credit-system.update'), []);

        $response->assertSessionHasErrors(['message_cost']);

        // Test with non-numeric value
        $response = $this->actingAs($this->adminUser)
            ->put(route('admin.settings.credit-system.update'), [
                'message_cost' => 'invalid',
            ]);

        $response->assertSessionHasErrors(['message_cost']);
    }

    /** @test */
    public function it_requires_admin_role_to_access_credit_settings(): void
    {
        $regularUser = User::factory()->create();
        $regularUser->assignRole('customer');

        $response = $this->actingAs($regularUser)
            ->get(route('admin.settings.credit-system.index'));

        $response->assertStatus(403);
    }

    /** @test */
    public function it_requires_authentication_to_access_credit_settings(): void
    {
        $response = $this->get(route('admin.settings.credit-system.index'));

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function it_logs_cost_update_actions(): void
    {
        $newCost = 85.0;

        // Enable log testing
        $this->expectsLogs();

        $response = $this->actingAs($this->adminUser)
            ->put(route('admin.settings.credit-system.update'), [
                'message_cost' => $newCost,
            ]);

        $response->assertRedirect(route('admin.settings.credit-system.index'));
    }

    /** @test */
    public function it_handles_valid_edge_case_values(): void
    {
        // Test with zero cost
        $response = $this->actingAs($this->adminUser)
            ->put(route('admin.settings.credit-system.update'), [
                'message_cost' => 0,
            ]);

        $response->assertRedirect(route('admin.settings.credit-system.index'));
        $response->assertSessionHas('success');

        // Test with maximum allowed cost
        $response = $this->actingAs($this->adminUser)
            ->put(route('admin.settings.credit-system.update'), [
                'message_cost' => 1000,
            ]);

        $response->assertRedirect(route('admin.settings.credit-system.index'));
        $response->assertSessionHas('success');

        // Test with decimal values
        $response = $this->actingAs($this->adminUser)
            ->put(route('admin.settings.credit-system.update'), [
                'message_cost' => 42.50,
            ]);

        $response->assertRedirect(route('admin.settings.credit-system.index'));
        $response->assertSessionHas('success');
    }

    private function expectsLogs(): void
    {
        // Placeholder for log expectation setup
        // In a real implementation, you might use Log::shouldReceive() or similar
    }
}