<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class CleanupWhatsAppSessions extends Command
{
    protected $signature = 'whatsapp:cleanup-sessions {--user-id= : Nettoyer pour un utilisateur spécifique} {--all : Nettoyer toutes les sessions}';
    protected $description = 'Nettoyage des sessions WhatsApp Bridge obsolètes';

    public function handle()
    {
        $userId = $this->option('user-id');
        $all = $this->option('all');

        if (! $userId && ! $all) {
            $this->error('Vous devez spécifier --user-id=X ou --all');

            return 1;
        }

        $this->info('=== Nettoyage des sessions WhatsApp Bridge ===');

        return $this->cleanupSessions($all, $userId ? (int) $userId : null);
    }

    private function cleanupSessions(bool $all = false, ?int $userId = null)
    {
        try {
            $baseUrl = config('services.whatsapp_bridge.url');

            $this->info('URL: '.$baseUrl);

            // Nettoyer par force brute les patterns connus
            $deleted = 0;
            $baseTime = time();

            // Essayer les sessions temporaires des 2 dernières heures
            for ($i = 0; $i < 120; $i++) { // 120 minutes = 2h
                $time = $baseTime - ($i * 60);

                // Générer quelques IDs potentiels avec ce timestamp
                for ($j = 0; $j < 10; $j++) {
                    $testUserId = $userId ?? ($j + 1); // Tester user 1-10 si pas spécifique
                    $sessionId = "temp_{$testUserId}_{$time}_".dechex(random_int(100000, 999999));

                    try {
                        $deleteResponse = Http::baseUrl($baseUrl)
                            ->timeout(5)
                            ->delete("/api/sessions/{$sessionId}");

                        if ($deleteResponse->successful()) {
                            $this->line("✅ Supprimé: {$sessionId}");
                            $deleted++;
                        }
                    } catch (\Exception $e) {
                        // Ignorer les erreurs (sessions qui n'existent pas)
                    }
                }

                if ($i % 20 == 0 && $i > 0) {
                    $this->line("Progression: {$i}/120 minutes vérifiées, {$deleted} supprimées");
                }
            }

            // Essayer aussi les sessions de test Laravel
            $testSessions = [
                'laravel-test-'.time(),
                'laravel-test-'.(time() - 300), // 5 min ago
                'laravel-test-'.(time() - 600), // 10 min ago
            ];

            foreach ($testSessions as $sessionId) {
                try {
                    $deleteResponse = Http::baseUrl($baseUrl)
                        ->timeout(5)
                        ->delete("/api/sessions/{$sessionId}");

                    if ($deleteResponse->successful()) {
                        $this->line("✅ Supprimé session de test: {$sessionId}");
                        $deleted++;
                    }
                } catch (\Exception $e) {
                    // Ignorer les erreurs
                }
            }

            $this->info("=== Nettoyage terminé: {$deleted} sessions supprimées ===");

        } catch (\Exception $e) {
            $this->error('Erreur: '.$e->getMessage());

            return 1;
        }

        return 0;
    }
}
