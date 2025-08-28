<?php

declare(strict_types=1);

require_once __DIR__.'/../../../vendor/autoload.php';

use App\Models\AiModel;
use App\Models\WhatsAppAccount;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Test optimisé du flow IncomingMessageController
 * Usage: php test-incoming-flow-complete.php
 */
final class IncomingFlowTester
{
    private const DEFAULT_TIMEOUT = 60;
    private const TEST_USER_ID = 2;
    private const TEST_PHONE = '237690000000';
    private const TEST_MESSAGE = 'Bonjour, j\'aimerais connaître vos produits disponibles.';

    public function __construct(
        private readonly string $baseUrl = 'http://localhost:8000',
        private string $sessionId = '',
    ) {
        $this->sessionId = 'test_session_'.uniqid();
    }

    public function run(): void
    {
        $this->log('🚀 DÉBUT TEST FLOW INCOMING MESSAGE');

        try {
            $this->verifyConfiguration();
            $account = $this->createTestAccount();
            $response = $this->sendRequest();
            $this->analyzeResponse($response);
            $this->log('✅ TEST COMPLETÉ AVEC SUCCÈS');
        } catch (Exception $e) {
            $this->logError($e);
        } finally {
            $this->cleanup();
        }
    }

    private function verifyConfiguration(): void
    {
        $this->log('🔍 Vérification configuration...');

        throw_unless(
            config('ai.default_provider'),
            new Exception('Provider AI non configuré')
        );

        throw_unless(
            env('DEEPSEEK_API_KEY'),
            new Exception('DEEPSEEK_API_KEY manquante')
        );

        throw_unless(
            AiModel::where('model_identifier', 'deepseek-chat')->exists(),
            new Exception('Modèle DeepSeek non trouvé')
        );

        $this->log('✅ Configuration valide');
    }

    private function createTestAccount(): WhatsAppAccount
    {
        $this->log('🔧 Création compte test...');

        // Cleanup précédent
        WhatsAppAccount::where('session_id', $this->sessionId)->delete();

        $model = AiModel::where('model_identifier', 'deepseek-chat')->first();

        $account = WhatsAppAccount::create([
            'user_id' => self::TEST_USER_ID,
            'session_id' => $this->sessionId,
            'session_name' => 'Test Session',
            'phone_number' => '+'.self::TEST_PHONE,
            'status' => 'connected',
            'ai_model_id' => $model->id,
            'agent_enabled' => true,
        ]);

        $this->log("✅ Compte créé (ID: {$account->id})");

        return $account;
    }

    private function sendRequest(): array
    {
        $this->log('📤 Envoi requête...');

        $data = [
            'event' => 'message.received',
            'session_id' => $this->sessionId,
            'session_name' => 'Test Session',
            'message' => [
                'id' => 'msg_'.uniqid(),
                'from' => self::TEST_PHONE,
                'body' => self::TEST_MESSAGE,
                'timestamp' => time(),
                'type' => 'text',
                'isGroup' => false,
            ],
        ];

        $response = Http::timeout(self::DEFAULT_TIMEOUT)
            ->post("{$this->baseUrl}/api/whatsapp/webhook/incoming-message", $data);

        throw_unless($response->successful(), new Exception(
            "HTTP {$response->status()}: {$response->body()}"
        ));

        return $response->json();
    }

    private function analyzeResponse(array $response): void
    {
        $this->log('📥 Analyse réponse...');

        // Validation structure
        collect(['success', 'processed'])->each(
            fn ($field) => throw_unless(
                array_key_exists($field, $response),
                new Exception("Champ manquant: {$field}")
            )
        );

        if ($response['success']) {
            $this->log('✅ Traitement réussi');

            if (isset($response['ai_response'])) {
                $this->log('🤖 Réponse AI: '.$response['ai_response']);
            }

            if (isset($response['processing_time'])) {
                $this->log("⏱️ Temps: {$response['processing_time']}ms");
            }
        } else {
            $this->log('❌ Échec: '.($response['error'] ?? 'Erreur inconnue'));
        }

        $this->log('📋 Réponse complète: '.json_encode($response, JSON_PRETTY_PRINT));
    }

    private function cleanup(): void
    {
        try {
            WhatsAppAccount::where('session_id', $this->sessionId)->delete();
            $this->log('🧹 Nettoyage terminé');
        } catch (Exception $e) {
            $this->log("⚠️ Erreur nettoyage: {$e->getMessage()}");
        }
    }

    private function log(string $message): void
    {
        $timestamp = date('H:i:s');
        echo "[{$timestamp}] {$message}\n";
        Log::info("[TEST-FLOW] {$message}");
    }

    private function logError(Exception $e): void
    {
        $this->log("❌ ERREUR: {$e->getMessage()}");
        $this->log("❌ Fichier: {$e->getFile()}:{$e->getLine()}");
    }
}

// Bootstrap & Run
$app = require_once __DIR__.'/../../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    (new IncomingFlowTester)->run();
} catch (Exception $e) {
    echo "❌ Erreur fatale: {$e->getMessage()}\n";
    exit(1);
}
