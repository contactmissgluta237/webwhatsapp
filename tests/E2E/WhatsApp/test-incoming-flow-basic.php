<?php

declare(strict_types=1);

require_once __DIR__.'/BaseTestIncomingMessage.php';

/**
 * Test basique du flow IncomingMessage sans produits
 */
class TestIncomingFlowBasic extends BaseTestIncomingMessage
{
    public function __construct()
    {
        parent::__construct('Test Flow Basic');
    }

    /**
     * Message de test pour une question générale
     */
    protected function getTestMessage(): string
    {
        return "Bonjour, j'aimerais connaître vos produits disponibles.";
    }

    /**
     * Pas de configuration spécifique pour le test basique
     */
    protected function setupTestSpecificData(): void
    {
        $this->log('📋 Configuration test basique (sans produits)');
        // Rien à faire pour le test basique
    }

    /**
     * Validations spécifiques pour le test basique
     */
    protected function performTestSpecificValidations(array $response): void
    {
        // Vérifier qu'il n'y a pas de produits dans la réponse
        if (! isset($response['products'])) {
            throw new Exception('Champ "products" manquant dans la réponse');
        }

        if (! is_array($response['products'])) {
            throw new Exception('Le champ "products" doit être un tableau');
        }

        if (! empty($response['products'])) {
            throw new Exception('Aucun produit ne devrait être retourné pour ce test');
        }

        // Vérifier la présence du message de réponse
        if (! isset($response['response_message']) || empty($response['response_message'])) {
            throw new Exception('Message de réponse manquant');
        }

        // Afficher la réponse complète pour analyse
        $this->log('📋 Réponse complète: '.json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $this->log('✅ Validation basique: Aucun produit retourné (attendu)');
        $this->log('✅ Validation basique: Message de réponse présent');

        // Attendre un peu pour que le stockage en base soit terminé
        sleep(2);

        // Vérification du stockage des messages en base de données
        $this->validateMessageStorage();
    }

    /**
     * Vérifie que les messages sont bien stockés en base de données
     */
    private function validateMessageStorage(): void
    {
        $this->log('🔍 Vérification stockage en base...');

        // Récupérer le compte WhatsApp par session_id
        $account = \App\Models\WhatsAppAccount::where('session_id', $this->webhookData['session_id'])->first();
        if (! $account) {
            throw new Exception('Compte WhatsApp non trouvé pour la session: '.$this->webhookData['session_id']);
        }

        $this->log("✅ Compte trouvé (ID: {$account->id})");

        // Récupérer les conversations liées à ce compte
        $conversations = $account->conversations()->get();
        $this->log("🔍 Nombre de conversations: {$conversations->count()}");

        // Si aucune conversation, vérifier s'il y en a dans toute la base
        if ($conversations->isEmpty()) {
            $allConversations = \App\Models\WhatsAppConversation::all();
            $this->log("🔍 Total conversations en base: {$allConversations->count()}");

            if ($allConversations->isNotEmpty()) {
                foreach ($allConversations as $conv) {
                    $this->log("🔍 Conversation trouvée: compte_id={$conv->whatsapp_account_id}, chat_id={$conv->chat_id}");
                }
            }

            throw new Exception('Aucune conversation trouvée pour ce compte');
        }

        $this->log("✅ {$conversations->count()} conversation(s) trouvée(s)");

        // Vérifier les messages pour cette session
        $totalMessages = 0;
        $inboundMessages = 0;
        $outboundMessages = 0;

        foreach ($conversations as $conversation) {
            $messages = $conversation->messages()->get();
            $totalMessages += $messages->count();

            foreach ($messages as $message) {
                if ($message->isInbound()) {
                    $inboundMessages++;
                    // Vérifier que le contenu correspond au message envoyé
                    if ($message->content === $this->getTestMessage()) {
                        $this->log('✅ Message entrant stocké correctement');
                    }
                } elseif ($message->isOutbound()) {
                    $outboundMessages++;
                    // Vérifier que c'est bien généré par l'IA
                    if ($message->is_ai_generated) {
                        $this->log('✅ Message sortant IA stocké correctement');
                    }
                }
            }
        }

        // Validations finales
        if ($totalMessages === 0) {
            throw new Exception('Aucun message stocké en base de données');
        }

        if ($inboundMessages === 0) {
            throw new Exception('Message entrant non stocké');
        }

        if ($outboundMessages === 0) {
            throw new Exception('Message sortant non stocké');
        }

        $this->log("✅ Stockage validé: {$totalMessages} messages ({$inboundMessages} entrants, {$outboundMessages} sortants)");
    }

    /**
     * Pas de nettoyage spécifique pour le test basique
     */
    protected function performTestSpecificCleanup(): void
    {
        // Rien à nettoyer de spécifique
    }
}

// Exécution du test
try {
    $tester = new TestIncomingFlowBasic;
    $tester->runTest();
} catch (Exception $e) {
    echo '❌ Erreur fatale: '.$e->getMessage().PHP_EOL;
    exit(1);
}
