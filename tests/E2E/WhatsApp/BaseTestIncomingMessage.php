<?php

declare(strict_types=1);

require_once __DIR__.'/../../../vendor/autoload.php';

use App\Http\Controllers\Customer\WhatsApp\Webhook\IncomingMessageController;
use App\Models\AiModel;
use App\Models\WhatsAppAccount;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Classe de base pour les tests de IncomingMessageController
 */
abstract class BaseTestIncomingMessage
{
    protected string $baseUrl;
    protected array $webhookData;
    protected ?WhatsAppAccount $testAccount = null;
    protected string $testName;

    public function __construct(string $testName = 'Test Flow')
    {
        $this->testName = $testName;
        $this->baseUrl = env('APP_URL', 'http://localhost:8000');
        $this->setupWebhookData();
    }

    /**
     * Point d'entrée principal pour exécuter le test
     */
    public function runTest(): void
    {
        $this->logTestStart();

        try {
            // 1. Vérification de la configuration AI
            $this->verifyAIConfiguration();

            // 2. Création d'un compte WhatsApp de test
            $this->createTestWhatsAppAccount();

            // 3. Configuration spécifique du test (implémentée par les classes filles)
            $this->setupTestSpecificData();

            // 4. Test du webhook
            $response = $this->sendWebhookRequest();

            // 5. Analyse de la réponse
            $this->analyzeResponse($response);

            // 6. Validations spécifiques du test
            $this->performTestSpecificValidations($response);

            $this->logTestSuccess();

        } catch (Exception $e) {
            $this->logTestError($e);
        } finally {
            // 7. Nettoyage
            $this->cleanupTestData();
        }
    }

    /**
     * Configuration des données webhook simulant NodeJS
     */
    protected function setupWebhookData(): void
    {
        $this->webhookData = [
            'event' => 'message.received',
            'session_id' => 'test_session_'.uniqid(),
            'session_name' => $this->testName,
            'message' => [
                'id' => 'msg_'.uniqid(),
                'from' => '237690000000',
                'body' => $this->getTestMessage(),
                'timestamp' => time(),
                'type' => 'text',
                'isGroup' => false,
                'participantId' => null,
            ],
            'contact' => [
                'id' => '237690000000',
                'name' => 'Test User',
                'number' => '237690000000',
                'isMyContact' => false,
            ],
        ];
    }

    /**
     * Vérifie la configuration AI (DeepSeek)
     */
    protected function verifyAIConfiguration(): void
    {
        $this->log('🔍 Vérification configuration...');

        // Lecture configuration AI
        $aiConfig = config('ai');
        $this->log("✅ Provider par défaut: {$aiConfig['default_provider']}");

        // Vérification configuration DeepSeek
        if (isset($aiConfig['providers']['deepseek'])) {
            $deepseekConfig = $aiConfig['providers']['deepseek'];
            $this->log("✅ Modèle DeepSeek: {$deepseekConfig['model_identifier']}");
            $this->log("✅ Endpoint: {$deepseekConfig['endpoint_url']}");
        }

        // Vérification présence des clés API
        $apiKey = env('DEEPSEEK_API_KEY');
        if (empty($apiKey)) {
            throw new Exception('DEEPSEEK_API_KEY non configurée');
        }
        $this->log('✅ Configuration valide');
    }

    /**
     * Crée un compte WhatsApp de test avec DeepSeek
     */
    protected function createTestWhatsAppAccount(): void
    {
        $this->log('🔧 Création compte test...');

        // Vérification du modèle DeepSeek
        $deepseekModel = AiModel::where('model_identifier', 'deepseek-chat')->first();
        if (! $deepseekModel) {
            throw new Exception('Modèle DeepSeek non trouvé en base de données');
        }

        // Suppression de l'ancien compte de test s'il existe
        WhatsAppAccount::where('session_id', $this->webhookData['session_id'])->delete();

        // Création du nouveau compte de test
        $this->testAccount = WhatsAppAccount::create([
            'user_id' => 1, // Utilisateur admin pour le test
            'session_id' => $this->webhookData['session_id'],
            'session_name' => $this->webhookData['session_name'],
            'phone_number' => '+'.$this->webhookData['message']['from'],
            'status' => 'connected',
            'ai_model_id' => $deepseekModel->id,
            'agent_enabled' => true,
        ]);

        $this->log("✅ Compte créé (ID: {$this->testAccount->id})");
    }

    /**
     * Envoie la requête webhook au contrôleur
     */
    protected function sendWebhookRequest(): array
    {
        $this->log('📤 Envoi requête...');
        $this->logWebhookData();

        $endpoint = $this->baseUrl.'/api/whatsapp/webhook/incoming-message';

        $response = Http::timeout(60)
            ->post($endpoint, $this->webhookData);

        if (! $response->successful()) {
            throw new Exception("Erreur HTTP {$response->status()}: ".$response->body());
        }

        return $response->json();
    }

    /**
     * Analyse la réponse de base
     */
    protected function analyzeResponse(array $response): void
    {
        $this->log('📥 Analyse réponse...');

        // Vérifications de base
        if (! isset($response['success'], $response['processed'])) {
            throw new Exception('Format de réponse invalide');
        }

        if (! $response['success'] || ! $response['processed']) {
            $error = $response['error'] ?? 'Erreur inconnue';
            throw new Exception("Traitement échoué: $error");
        }

        $this->log('✅ Traitement réussi');
    }

    /**
     * Nettoie les données de test
     */
    protected function cleanupTestData(): void
    {
        try {
            $this->log('🧹 Nettoyage...');

            // Suppression du compte de test
            if ($this->testAccount) {
                $this->cleanupTestAccount();
            }

            // Nettoyage spécifique du test
            $this->performTestSpecificCleanup();

            $this->log('✅ Nettoyage terminé');
        } catch (Exception $e) {
            $this->log('⚠️ Erreur lors du nettoyage: '.$e->getMessage());
        }
    }

    /**
     * Nettoie le compte de test créé
     */
    protected function cleanupTestAccount(): void
    {
        $deleted = WhatsAppAccount::where('session_id', $this->webhookData['session_id'])->delete();
        if ($deleted > 0) {
            $this->log('✅ Compte supprimé');
        }
    }

    /**
     * Affiche les données webhook pour débogage
     */
    protected function logWebhookData(): void
    {
        $this->log("Session ID: {$this->webhookData['session_id']}");
        $this->log("Message ID: {$this->webhookData['message']['id']}");
        $this->log("From: {$this->webhookData['message']['from']}");
        $this->log("Body: {$this->webhookData['message']['body']}");
        $this->log("Type: {$this->webhookData['message']['type']}");
    }

    /**
     * Logs de début de test
     */
    protected function logTestStart(): void
    {
        $this->log('🚀 DÉBUT '.strtoupper($this->testName));
        $this->log('🌐 Base URL: '.$this->baseUrl);
        $this->log('📅 Date: '.date('Y-m-d H:i:s'));
    }

    /**
     * Logs de succès
     */
    protected function logTestSuccess(): void
    {
        $this->log('✅ TEST COMPLETÉ AVEC SUCCÈS');
    }

    /**
     * Logs d'erreur
     */
    protected function logTestError(Exception $e): void
    {
        $this->log('❌ ERREUR DURANT LE TEST');
        $this->log('❌ Message: '.$e->getMessage());
        $this->log('❌ Fichier: '.$e->getFile().':'.$e->getLine());
        $this->log('❌ Trace:');
        foreach (explode("\n", $e->getTraceAsString()) as $line) {
            if (trim($line)) {
                $this->log('❌ '.trim($line));
            }
        }
    }

    /**
     * Log avec timestamp
     */
    protected function log(string $message): void
    {
        $timestamp = date('H:i:s');
        echo "[{$timestamp}] {$message}".PHP_EOL;

        // Log aussi dans Laravel
        if (class_exists('Illuminate\Support\Facades\Log')) {
            Log::info("[{$this->testName}] {$message}");
        }
    }

    // ==============================================
    // MÉTHODES ABSTRAITES À IMPLÉMENTER
    // ==============================================

    /**
     * Retourne le message à tester
     */
    abstract protected function getTestMessage(): string;

    /**
     * Configuration spécifique au test (produits, etc.)
     */
    abstract protected function setupTestSpecificData(): void;

    /**
     * Validations spécifiques au test
     */
    abstract protected function performTestSpecificValidations(array $response): void;

    /**
     * Nettoyage spécifique au test
     */
    abstract protected function performTestSpecificCleanup(): void;
}

// Bootstrap Laravel
// Force l'environnement local pour les tests E2E (même avec php artisan test)
$_ENV['APP_ENV'] = 'local';
$_SERVER['APP_ENV'] = 'local';
putenv('APP_ENV=local');

$app = require_once __DIR__.'/../../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
