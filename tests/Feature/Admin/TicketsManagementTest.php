<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class TicketsManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        // Créer les rôles nécessaires
        \Spatie\Permission\Models\Role::create(['name' => 'admin']);
        \Spatie\Permission\Models\Role::create(['name' => 'customer']);

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

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
        $this->admin = $this->admin->fresh();

        $this->customer = User::factory()->create();
        $this->customer->assignRole('customer');
        $this->customer = $this->customer->fresh();
    }

    #[Test]
    public function admin_can_access_tickets_index(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.tickets.index'))
            ->assertOk()
            ->assertViewIs('admin.tickets.index');
    }

    #[Test]
    public function admin_can_view_specific_ticket(): void
    {
        // Créer un ticket pour tester
        $ticket = Ticket::factory()->create([
            'user_id' => $this->customer->id,
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.tickets.show', $ticket))
            ->assertOk()
            ->assertViewIs('admin.tickets.show')
            ->assertViewHas('ticket', $ticket);
    }

    #[Test]
    public function customer_cannot_access_admin_tickets(): void
    {
        // Créer un ticket pour tester
        $ticket = Ticket::factory()->create([
            'user_id' => $this->admin->id,
        ]);

        $this->actingAs($this->customer)
            ->get(route('admin.tickets.index'))
            ->assertForbidden();

        $this->actingAs($this->customer)
            ->get(route('admin.tickets.show', $ticket))
            ->assertForbidden();
    }

    #[Test]
    public function guest_cannot_access_admin_tickets(): void
    {
        // Créer un ticket pour tester
        $ticket = Ticket::factory()->create([
            'user_id' => $this->customer->id,
        ]);

        $this->get(route('admin.tickets.index'))
            ->assertRedirect(route('login'));

        $this->get(route('admin.tickets.show', $ticket))
            ->assertRedirect(route('login'));
    }
}