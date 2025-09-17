<?php

declare(strict_types=1);

namespace Tests\Feature\Customer;

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
    public function customer_can_access_tickets_index(): void
    {
        $this->actingAs($this->customer)
            ->get(route('customer.tickets.index'))
            ->assertOk()
            ->assertViewIs('customer.tickets.index');
    }

    #[Test]
    public function customer_can_access_create_ticket_page(): void
    {
        $this->actingAs($this->customer)
            ->get(route('customer.tickets.create'))
            ->assertOk()
            ->assertViewIs('customer.tickets.create');
    }

    #[Test]
    public function customer_can_view_own_ticket(): void
    {
        // Créer un ticket appartenant au customer
        $ticket = Ticket::factory()->create([
            'user_id' => $this->customer->id,
        ]);

        $this->actingAs($this->customer)
            ->get(route('customer.tickets.show', $ticket))
            ->assertOk()
            ->assertViewIs('customer.tickets.show')
            ->assertViewHas('ticket', $ticket);
    }

    #[Test]
    public function customer_cannot_view_other_users_tickets(): void
    {
        // Créer un ticket appartenant à un autre utilisateur
        $otherTicket = Ticket::factory()->create([
            'user_id' => $this->admin->id,
        ]);

        $this->actingAs($this->customer)
            ->get(route('customer.tickets.show', $otherTicket))
            ->assertForbidden();
    }

    #[Test]
    public function admin_cannot_access_customer_tickets_without_customer_role(): void
    {
        $ticket = Ticket::factory()->create([
            'user_id' => $this->customer->id,
        ]);

        $this->actingAs($this->admin)
            ->get(route('customer.tickets.index'))
            ->assertForbidden();

        $this->actingAs($this->admin)
            ->get(route('customer.tickets.create'))
            ->assertForbidden();

        $this->actingAs($this->admin)
            ->get(route('customer.tickets.show', $ticket))
            ->assertForbidden();
    }

    #[Test]
    public function guest_cannot_access_customer_tickets(): void
    {
        $ticket = Ticket::factory()->create([
            'user_id' => $this->customer->id,
        ]);

        $this->get(route('customer.tickets.index'))
            ->assertRedirect(route('login'));

        $this->get(route('customer.tickets.create'))
            ->assertRedirect(route('login'));

        $this->get(route('customer.tickets.show', $ticket))
            ->assertRedirect(route('login'));
    }
}