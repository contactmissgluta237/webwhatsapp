<?php

namespace App\Console\Commands;

use Database\Seeders\CustomerTestDataSeeder;
use Illuminate\Console\Command;

class GenerateCustomerTestData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'customer:generate-test-data {--fresh : Supprimer les données existantes d\'abord}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Génère des données de test pour customer1@example.com (conversations, agent, messages)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Génération des données de test pour customer1@example.com...');
        $this->line('');

        if ($this->option('fresh')) {
            $this->warn('⚠️  Mode fresh activé - suppression des données existantes...');
            
            // Supprimer les données existantes pour cet utilisateur
            $this->call('db:seed', [
                '--class' => CustomerTestDataSeeder::class,
            ]);
        } else {
            // Juste ajouter les données
            $this->call('db:seed', [
                '--class' => CustomerTestDataSeeder::class,
            ]);
        }

        $this->line('');
        $this->info('✅ Génération terminée !');
        $this->line('');
        $this->comment('Tu peux maintenant tester avec:');
        $this->comment('  • Email: customer1@example.com');
        $this->comment('  • Mot de passe: password');
        
        return Command::SUCCESS;
    }
}
