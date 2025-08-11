<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class TicketSeeder extends Seeder
{
    public function run(): void
    {
        $customers = User::role('customer')->take(10)->get();
        $admins = User::role('admin')->take(3)->get();

        if ($customers->isEmpty()) {
            $this->command->warn('Aucun client trouvé. Veuillez d\'abord exécuter CustomerSeeder.');

            return;
        }

        if ($admins->isEmpty()) {
            $this->command->warn('Aucun admin trouvé. Veuillez d\'abord créer des utilisateurs admin.');

            return;
        }

        $this->command->info('Création de tickets de test...');
        $ticketCount = 0;

        DB::transaction(function () use ($customers, $admins, &$ticketCount) {
            foreach ($customers as $customer) {
                $ticketsForCustomer = rand(1, 3);

                for ($i = 0; $i < $ticketsForCustomer; $i++) {
                    $ticket = Ticket::create([
                        'user_id' => $customer->id,
                        'assigned_to' => $admins->random()->id,
                        'title' => fake()->sentence(),
                        'description' => fake()->paragraph(),
                        'status' => collect(TicketStatus::values())->random(),
                        'priority' => collect(TicketPriority::values())->random(),
                    ]);

                    TicketMessage::create([
                        'ticket_id' => $ticket->id,
                        'user_id' => $customer->id,
                        'message' => $ticket->description,
                        'sender_type' => 'customer',
                    ]);

                    if (rand(0, 100) > 50) {
                        TicketMessage::create([
                            'ticket_id' => $ticket->id,
                            'user_id' => $admins->random()->id,
                            'message' => fake()->paragraph(),
                            'sender_type' => 'admin',
                        ]);
                    }

                    $ticketCount++;
                }
            }
        });

        $this->command->info("✅ {$ticketCount} tickets créés avec succès !");
    }
}
