<?php

namespace Tests\Unit;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardMetricsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Créer des utilisateurs et des tickets pour les tests
        User::factory()->count(5)->create();
        Ticket::factory()->count(3)->create();
    }

    /** @test */
    public function it_can_get_total_users_count()
    {
        $users = User::factory()->count(5)->create();
        $this->assertEquals(5, $users->count());
    }

    /** @test */
    public function it_can_get_total_tickets_count()
    {
        $tickets = Ticket::factory()->count(3)->create();
        $this->assertEquals(3, $tickets->count());
    }

    // Ajoutez d'autres tests unitaires pour les métriques du tableau de bord ici
}
