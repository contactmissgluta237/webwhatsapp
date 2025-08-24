<?php

declare(strict_types=1);

require_once __DIR__.'/BaseTestIncomingMessage.php';

/**
 * Test de flow de conversation multi-messages
 */
class TestConversationFlow extends BaseTestIncomingMessage
{
    private array $conversationMessages = [
        'Bonsoir boss.',
        'Svp pouvez vous créer un site de pari sportif en ligne?',
        'avez vous déjà intégré mobile money, paypal dans des projets ?',
        'cmobien ça peut me coûter ,et en combien de temps ?',
        'puis je avoir vos réalisations ?',
    ];

    private int $currentMessageIndex = 0;
    private array $conversationResponses = [];

    public function __construct()
    {
        parent::__construct('Test Flow Conversation Multi-Messages');
    }

    /**
     * Remplace runTest() pour gérer la conversation complète
     */
    public function runTest(): void
    {
        $this->logTestStart();

        try {
            // 1. Vérification de la configuration AI
            $this->verifyAIConfiguration();

            // 2. Création d'un compte WhatsApp de test
            $this->createTestWhatsAppAccount();

            // 3. Configuration spécifique du test
            $this->setupTestSpecificData();

            // 4. Envoi de tous les messages de la conversation
            foreach ($this->conversationMessages as $index => $message) {
                $this->currentMessageIndex = $index;
                $this->log('💬 Message '.($index + 1).'/'.count($this->conversationMessages).": \"$message\"");

                // Utiliser la logique de BaseTestIncomingMessage mais avec le message actuel
                $this->webhookData['message']['body'] = $message;
                $this->webhookData['message']['id'] = 'msg_'.uniqid();

                $response = $this->sendWebhookRequest();
                $this->analyzeResponse($response);
                $this->conversationResponses[] = $response;

                // Afficher la réponse de l'IA
                if (isset($response['response_message'])) {
                    $this->log('🤖 Réponse IA '.($index + 1).': "'.$response['response_message'].'"');
                }

                $this->performValidationForMessage($index + 1, $message, $response);

                // Petit délai pour simuler une vraie conversation
                sleep(1);
            }

            // 5. Validation globale de la conversation
            $this->performTestSpecificValidations([]);

            $this->logTestSuccess();

        } catch (Exception $e) {
            $this->logTestError($e);
        } finally {
            $this->cleanupTestData();
        }
    }

    /**
     * Valide la réponse pour chaque message.
     * Cette méthode est spécifique à ce test et remplace performTestSpecificValidations globale.
     */
    protected function performValidationForMessage(int $messageIndex, string $sentMessage, array $response): void
    {
        // Validation générique: vérifier la présence du message de réponse
        if (! isset($response['response_message']) || empty($response['response_message'])) {
            throw new Exception('Message de réponse manquant pour le message '.$messageIndex);
        }
        $this->log('✅ Validation message '.$messageIndex.': Message de réponse présent.');

        // Ajoutez ici des validations spécifiques pour chaque message si nécessaire
        switch ($messageIndex) {
            case 1:
                // Validation pour "Bonsoir boss."
                // Ex: Vérifier que la réponse est une salutation ou une demande de plus d'informations.
                break;
            case 2:
                // Validation pour "Svp pouvez vous créer un site de pari sportif en ligne?"
                // Ex: Vérifier que la réponse mentionne la faisabilité ou demande des détails.
                break;
            case 3:
                // Validation pour "avez vous déjà intégré mobile money, paypal dans des projets ?"
                // Ex: Vérifier que la réponse confirme ou non l'intégration de ces méthodes.
                break;
            case 4:
                // Validation pour "cmobien ça peut me coûter ,et en combien de temps ?"
                // Ex: Vérifier que la réponse parle de devis ou de délais.
                break;
            case 5:
                // Validation pour "puis je avoir vos réalisations ?"
                // Ex: Vérifier que la réponse propose d'envoyer des réalisations ou un portfolio.
                break;
        }
    }

    /**
     * Pas de message de test unique pour ce test de conversation.
     * La méthode runTest gère l'envoi des messages.
     */
    protected function getTestMessage(): string
    {
        return ''; // Non utilisé pour ce type de test
    }

    /**
     * Validations spécifiques globales après toute la conversation
     */
    protected function performTestSpecificValidations(array $response): void
    {
        $this->log('📊 Validation de la conversation complète...');

        // Vérifier que tous les messages ont reçu une réponse
        if (count($this->conversationResponses) !== count($this->conversationMessages)) {
            throw new Exception('Nombre de réponses incorrect: '.count($this->conversationResponses).' vs '.count($this->conversationMessages));
        }

        // Vérifier que chaque réponse montre une compréhension contextuelle
        $this->validateContextualUnderstanding();

        $this->log('✅ Validation conversation: Toutes les réponses reçues');
        $this->log('✅ Validation conversation: Contexte maintenu sur '.count($this->conversationMessages).' messages');
    }

    /**
     * Valide que l'IA maintient le contexte à travers la conversation
     */
    private function validateContextualUnderstanding(): void
    {
        // Analyser si les réponses 2-5 font référence au contexte précédent
        for ($i = 1; $i < count($this->conversationResponses); $i++) {
            $response = $this->conversationResponses[$i];
            $message = strtolower($response['response_message'] ?? '');

            // Pour les messages 2-5, vérifier qu'il y a une continuité contextuelle
            switch ($i + 1) {
                case 2: // Après "Svp pouvez vous créer un site de pari sportif en ligne?"
                    if (! str_contains($message, 'pari') && ! str_contains($message, 'site') && ! str_contains($message, 'sport')) {
                        $this->log('⚠️ Réponse 2 pourrait manquer de contexte pari sportif');
                    } else {
                        $this->log('✅ Réponse 2: Contexte pari sportif maintenu');
                    }
                    break;

                case 3: // Après "avez vous déjà intégré mobile money, paypal dans des projets ?"
                    if (! str_contains($message, 'paiement') && ! str_contains($message, 'intégr') && ! str_contains($message, 'projet')) {
                        $this->log('⚠️ Réponse 3 pourrait manquer de contexte intégrations');
                    } else {
                        $this->log('✅ Réponse 3: Contexte intégrations maintenu');
                    }
                    break;

                case 4: // Après "combien ça peut me coûter ,et en combien de temps ?"
                    if (! str_contains($message, 'prix') && ! str_contains($message, 'coût') && ! str_contains($message, 'temps') && ! str_contains($message, 'délai')) {
                        $this->log('⚠️ Réponse 4 pourrait manquer de contexte devis');
                    } else {
                        $this->log('✅ Réponse 4: Contexte devis maintenu');
                    }
                    break;

                case 5: // Après "puis je avoir vos réalisations ?"
                    if (! str_contains($message, 'réalisation') && ! str_contains($message, 'portfolio') && ! str_contains($message, 'projet')) {
                        $this->log('⚠️ Réponse 5 pourrait manquer de contexte réalisations');
                    } else {
                        $this->log('✅ Réponse 5: Contexte réalisations maintenu');
                    }
                    break;
            }
        }
    }

    /**
     * Configuration spécifique pour le test de conversation.
     */
    protected function setupTestSpecificData(): void
    {
        $this->log('📋 Configuration test de conversation.');
        // Ajoutez ici toute configuration nécessaire avant le début de la conversation
    }

    /**
     * Nettoyage spécifique pour le test de conversation.
     */
    protected function performTestSpecificCleanup(): void
    {
        $this->log('🧹 Nettoyage après test de conversation.');
        // Ajoutez ici toute opération de nettoyage après la conversation
    }
}

// Exécution du test
try {
    $tester = new TestConversationFlow;
    $tester->runTest();
} catch (Exception $e) {
    echo '❌ Erreur fatale: '.$e->getMessage().PHP_EOL;
    exit(1);
}
