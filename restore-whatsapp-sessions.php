<?php

declare(strict_types=1);

/**
 * Script de restauration des sessions WhatsApp actives
 *
 * Ce script récupère les sessions actives depuis Node.js et les recrée dans Laravel
 * Utiliser après un migrate:fresh --seed pour restaurer les sessions perdues
 */

require_once 'vendor/autoload.php';

use App\Enums\WhatsAppStatus;
use App\Models\User;
use App\Models\WhatsAppAccount;
use Illuminate\Support\Facades\Log;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

class WhatsAppSessionRestorer
{
    private string $nodeJsUrl;
    private array $restoredSessions = [];
    private array $errors = [];

    public function __construct(string $nodeJsUrl = 'http://localhost:3000')
    {
        $this->nodeJsUrl = $nodeJsUrl;
    }

    public function restore(): void
    {
        $this->info('🔄 Début de la restauration des sessions WhatsApp');

        try {
            $sessions = $this->fetchActiveSessions();

            if (empty($sessions)) {
                $this->warning('⚠️ Aucune session active trouvée sur Node.js');

                return;
            }

            $this->info('📋 '.count($sessions).' session(s) active(s) trouvée(s)');

            foreach ($sessions as $session) {
                $this->restoreSession($session);
            }

            $this->displayResults();

        } catch (\Exception $e) {
            $this->error('❌ Erreur lors de la restauration: '.$e->getMessage());
            Log::error('Session restore failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    private function fetchActiveSessions(): array
    {
        $this->info('🌐 Récupération des sessions depuis Node.js...');

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->nodeJsUrl.'/api/sessions',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_FOLLOWLOCATION => true,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new \Exception('Erreur cURL: '.$error);
        }

        if ($httpCode !== 200) {
            throw new \Exception("HTTP Error $httpCode lors de la récupération des sessions");
        }

        $data = json_decode($response, true);

        if (! $data || ! isset($data['sessions'])) {
            throw new \Exception('Réponse invalide de Node.js: '.$response);
        }

        // Filtrer seulement les sessions connectées
        return array_filter($data['sessions'], function ($session) {
            return isset($session['status']) && $session['status'] === 'connected';
        });
    }

    private function restoreSession(array $sessionData): void
    {
        $sessionId = $sessionData['sessionId'] ?? null;
        $userId = $sessionData['userId'] ?? null;
        $phoneNumber = $sessionData['phoneNumber'] ?? null;

        if (! $sessionId || ! $userId) {
            $this->errors[] = 'Session invalide - données manquantes: '.json_encode($sessionData);

            return;
        }

        try {
            // Vérifier si l'utilisateur existe
            $user = User::find($userId);
            if (! $user) {
                $this->errors[] = "Utilisateur introuvable (ID: $userId) pour session $sessionId";

                return;
            }

            // Vérifier si la session n'existe pas déjà
            $existingAccount = WhatsAppAccount::where('session_id', $sessionId)->first();
            if ($existingAccount) {
                $this->warning("⚠️ Session $sessionId existe déjà - mise à jour du statut");

                $existingAccount->update([
                    'status' => WhatsAppStatus::CONNECTED(),
                    'phone_number' => $phoneNumber,
                    'last_seen_at' => now(),
                    'session_data' => $sessionData,
                ]);

                $this->restoredSessions[] = "Mise à jour: $sessionId ($phoneNumber)";

                return;
            }

            // Créer le nouvel account WhatsApp
            $whatsappAccount = WhatsAppAccount::create([
                'user_id' => $userId,
                'session_name' => $this->generateSessionName($phoneNumber, $sessionId),
                'session_id' => $sessionId,
                'phone_number' => $phoneNumber,
                'status' => WhatsAppStatus::CONNECTED(),
                'last_seen_at' => isset($sessionData['lastActivity'])
                    ? \Carbon\Carbon::parse($sessionData['lastActivity'])
                    : now(),
                'session_data' => $sessionData,

                // Valeurs par défaut pour les autres champs
                'agent_enabled' => false,
                'response_time' => 'random',
                'stop_on_human_reply' => false,
                'daily_ai_responses' => 0,
            ]);

            $this->success("✅ Session restaurée: $sessionId ($phoneNumber) pour utilisateur $userId");
            $this->restoredSessions[] = "$sessionId ($phoneNumber)";

            Log::info('WhatsApp session restored', [
                'session_id' => $sessionId,
                'user_id' => $userId,
                'phone_number' => $phoneNumber,
                'account_id' => $whatsappAccount->id,
            ]);

        } catch (\Exception $e) {
            $this->errors[] = "Erreur lors de la restauration de $sessionId: ".$e->getMessage();
            Log::error('Failed to restore session', [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
                'session_data' => $sessionData,
            ]);
        }
    }

    private function generateSessionName(?string $phoneNumber, string $sessionId): string
    {
        if ($phoneNumber) {
            return 'WhatsApp '.substr($phoneNumber, -4);
        }

        // Extraire une partie lisible du session_id
        $parts = explode('_', $sessionId);
        if (count($parts) >= 3) {
            return 'Session '.substr($parts[2], 0, 8);
        }

        return 'Session '.substr($sessionId, -8);
    }

    private function displayResults(): void
    {
        echo "\n".str_repeat('=', 60)."\n";
        echo "📊 RÉSULTATS DE LA RESTAURATION\n";
        echo str_repeat('=', 60)."\n";

        if (! empty($this->restoredSessions)) {
            $this->success('✅ Sessions restaurées ('.count($this->restoredSessions).'):');
            foreach ($this->restoredSessions as $session) {
                echo "  • $session\n";
            }
        }

        if (! empty($this->errors)) {
            echo "\n";
            $this->error('❌ Erreurs rencontrées ('.count($this->errors).'):');
            foreach ($this->errors as $error) {
                echo "  • $error\n";
            }
        }

        if (empty($this->restoredSessions) && empty($this->errors)) {
            $this->info('ℹ️ Aucune session à restaurer');
        }

        echo "\n".str_repeat('=', 60)."\n";
    }

    private function info(string $message): void
    {
        echo "\033[36m$message\033[0m\n";
    }

    private function success(string $message): void
    {
        echo "\033[32m$message\033[0m\n";
    }

    private function warning(string $message): void
    {
        echo "\033[33m$message\033[0m\n";
    }

    private function error(string $message): void
    {
        echo "\033[31m$message\033[0m\n";
    }
}

// Exécution du script
if (php_sapi_name() === 'cli') {
    try {
        $restorer = new WhatsAppSessionRestorer;
        $restorer->restore();

        echo "\n🎉 Script terminé avec succès!\n";
        echo "💡 Vous pouvez maintenant vérifier vos sessions dans Laravel.\n\n";

    } catch (\Exception $e) {
        echo "\n💥 Erreur fatale: ".$e->getMessage()."\n\n";
        exit(1);
    }
} else {
    echo "⚠️ Ce script doit être exécuté en ligne de commande (CLI).\n";
    echo "Usage: php restore-whatsapp-sessions.php\n";
    exit(1);
}
