<?php

namespace Database\Seeders;

use App\Models\Geography\Country;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // S'assurer qu'on a au moins un pays
        $country = Country::where('code', 'CM')->first() ?? Country::first();

        if (! $country) {
            $this->command->warn('Aucun pays trouvé. Veuillez d\'abord exécuter CountrySeeder.');

            return;
        }

        $this->command->info('Création de 30 clients de test...');
        $customerCount = 0;

        DB::transaction(function () use ($country, &$customerCount) {
            $users = [];

            // Créer 30 utilisateurs clients
            for ($i = 1; $i <= 30; $i++) {
                $user = User::factory()->customer()->create([
                    'country_id' => $country->id,
                    'first_name' => fake()->firstName(),
                    'last_name' => fake()->lastName(),
                    'email' => "customer{$i}@example.com",
                    'phone_number' => fake()->phoneNumber(),
                    'address' => fake()->address(),
                ]);

                $user->wallet()->create(['balance' => 0]);

                $users[] = $user;
            }

            $customerCount = count($users);

            // Créer quelques relations de parrainage (environ 30% des clients ont un parrain)
            $this->createReferralRelationships($users);
        });

        $this->command->info("✅ {$customerCount} clients créés avec succès !");
    }

    /**
     * Créer des relations de parrainage entre les clients
     */
    private function createReferralRelationships(array $users): void
    {
        $referralCount = 0;

        // Prendre environ 30% des clients pour avoir un parrain
        $usersToRefer = collect($users)->random(9); // 9 clients sur 30

        foreach ($usersToRefer as $user) {
            // Choisir un parrain parmi les autres clients (pas lui-même)
            $potentialReferrers = collect($users)
                ->filter(fn ($u) => $u->id !== $user->id)
                ->random(1);

            if ($potentialReferrers->isNotEmpty()) {
                $referrer = $potentialReferrers->first();

                $user->update([
                    'referrer_id' => $referrer->id,
                ]);

                $referralCount++;
            }
        }

        $this->command->info("✅ {$referralCount} relations de parrainage créées.");
    }
}
