<?php

namespace Database\Seeders;

use App\Models\Package;
use Illuminate\Database\Seeder;

class PackagesSeeder extends Seeder
{
    public function run(): void
    {
        // Package TRIAL (gratuit, 7 jours, une seule fois)
        Package::create([
            'name' => 'trial',
            'display_name' => 'Essai Gratuit',
            'description' => 'Testez notre service pendant 7 jours gratuitement. Limité à une seule utilisation par utilisateur.',
            'price' => 0,
            'currency' => 'XAF',
            'messages_limit' => 50,
            'context_limit' => 2000,
            'accounts_limit' => 1,
            'products_limit' => 0,
            'duration_days' => 7,
            'is_recurring' => false,
            'one_time_only' => true,
            'features' => [],
            'is_active' => true,
            'sort_order' => 1,
        ]);

        // Package STARTER (2000 XAF/mois)
        Package::create([
            'name' => 'starter',
            'display_name' => 'Starter',
            'description' => 'Parfait pour commencer avec votre agent WhatsApp IA.',
            'price' => 2000,
            'currency' => 'XAF',
            'messages_limit' => 200,
            'context_limit' => 3000,
            'accounts_limit' => 1,
            'products_limit' => 0,
            'duration_days' => 30,
            'is_recurring' => true,
            'one_time_only' => false,
            'features' => [],
            'is_active' => true,
            'sort_order' => 2,
        ]);

        // Package PRO (5000 XAF/mois) - Interverti avec ancien business
        Package::create([
            'name' => 'pro',
            'display_name' => 'Pro',
            'description' => 'Idéal pour les professionnels avec gestion de produits et comptes multiples.',
            'price' => 5000,
            'currency' => 'XAF',
            'messages_limit' => 600,
            'context_limit' => 5000,
            'accounts_limit' => 2,
            'products_limit' => 5,
            'duration_days' => 30,
            'is_recurring' => true,
            'one_time_only' => false,
            'features' => [],
            'is_active' => true,
            'sort_order' => 3,
        ]);

        // Package BUSINESS (10000 XAF/mois) - Interverti avec ancien pro
        Package::create([
            'name' => 'business',
            'display_name' => 'Business',
            'description' => 'Pour les entreprises exigeantes avec rapports hebdomadaires et support prioritaire.',
            'price' => 10000,
            'currency' => 'XAF',
            'messages_limit' => 1300,
            'context_limit' => 10000,
            'accounts_limit' => 5,
            'products_limit' => 10,
            'duration_days' => 30,
            'is_recurring' => true,
            'one_time_only' => false,
            'features' => [
                'weekly_reports',
                'priority_support',
            ],
            'is_active' => true,
            'sort_order' => 4,
        ]);
    }
}
