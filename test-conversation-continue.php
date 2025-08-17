<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Contracts\WhatsApp\WhatsAppMessageOrchestratorInterface;
use App\DTOs\WhatsApp\WhatsAppAccountMetadataDTO;
use App\Models\WhatsAppAccount;

// Configuration Laravel pour le test
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

try {
    echo "🧪 Test de conversation continue (5 messages)...\n\n";
    
    // Test avec un compte réel
    $account = WhatsAppAccount::first();
    if (!$account) {
        echo "❌ Aucun compte WhatsApp trouvé\n";
        exit(1);
    }
    
    echo "✅ Compte trouvé: {$account->session_name}\n";
    
    // Créer les métadonnées
    $accountMetadata = new WhatsAppAccountMetadataDTO(
        sessionId: 'test_conversation',
        sessionName: $account->session_name,
        accountId: $account->id,
        agentEnabled: true,
        agentPrompt: $account->agent_prompt ?? 'Tu es un assistant utile',
        aiModelId: $account->ai_model_id,
        responseTime: 'random',
        contextualInformation: $account->contextual_information ?? '',
        settings: []
    );
    
    // Obtenir l'orchestrateur
    $orchestrator = app(WhatsAppMessageOrchestratorInterface::class);
    
    // Simulation d'une conversation de 5 messages
    $conversationContext = [];
    $messages = [
        "Bonjour, comment allez-vous ?",
        "Très bien ! Pouvez-vous me dire quels sont vos services ?",
        "Combien ça coûte ?",
        "Avez-vous des exemples de projets ?",
        "Comment puis-je vous contacter ?"
    ];
    
    foreach ($messages as $index => $userMessage) {
        echo "\n📨 Message " . ($index + 1) . ": '$userMessage'\n";
        echo "   Contexte actuel: " . count($conversationContext) . " messages\n";
        
        try {
            $response = $orchestrator->processSimulatedMessage($accountMetadata, $userMessage, $conversationContext);
            
            if ($response && $response->hasAiResponse) {
                echo "   ✅ Réponse: " . substr($response->aiResponse, 0, 100) . "...\n";
                
                // Ajouter au contexte pour le prochain message
                $conversationContext[] = ['role' => 'user', 'content' => $userMessage];
                $conversationContext[] = ['role' => 'assistant', 'content' => $response->aiResponse];
                
                echo "   📊 Contexte maintenant: " . count($conversationContext) . " messages\n";
            } else {
                echo "   ❌ ÉCHEC: Aucune réponse générée\n";
                break;
            }
        } catch (\Exception $e) {
            echo "   ❌ ERREUR: " . $e->getMessage() . "\n";
            break;
        }
    }
    
    echo "\n🏁 Test terminé\n";
    
} catch (\Exception $e) {
    echo "❌ ERREUR GLOBALE: " . $e->getMessage() . "\n";
    echo "   Fichier: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
