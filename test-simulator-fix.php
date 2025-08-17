<?php

require_once __DIR__.'/vendor/autoload.php';

use App\Contracts\WhatsApp\WhatsAppMessageOrchestratorInterface;
use App\DTOs\WhatsApp\WhatsAppAccountMetadataDTO;
use App\Models\WhatsAppAccount;

// Configuration Laravel pour le test
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

try {
    echo "🧪 Test rapide du simulateur...\n\n";

    // Test avec un compte réel
    $account = WhatsAppAccount::first();
    if (! $account) {
        echo "❌ Aucun compte WhatsApp trouvé\n";
        exit(1);
    }

    echo "✅ Compte trouvé: {$account->session_name}\n";

    // Créer les métadonnées
    $accountMetadata = new WhatsAppAccountMetadataDTO(
        sessionId: 'test_simulator',
        sessionName: $account->session_name,
        accountId: $account->id,
        agentEnabled: true,
        agentPrompt: $account->agent_prompt ?? 'Tu es un assistant utile',
        aiModelId: $account->ai_model_id,
        responseTime: 'random',
        contextualInformation: $account->contextual_information ?? '',
        settings: []
    );

    echo "✅ Métadonnées créées\n";

    // Obtenir l'orchestrateur
    $orchestrator = app(WhatsAppMessageOrchestratorInterface::class);
    echo "✅ Orchestrateur obtenu\n";

    // Test de simulation
    $userMessage = 'Bonjour, comment allez-vous ?';
    $context = []; // Pas de contexte pour ce test simple

    echo "🚀 Test de simulation avec message: '$userMessage'\n";

    $response = $orchestrator->processSimulatedMessage($accountMetadata, $userMessage, $context);

    if ($response && $response->hasAiResponse) {
        echo "✅ SUCCÈS ! Réponse générée:\n";
        echo '📄 Réponse: '.substr($response->aiResponse, 0, 100)."...\n";
        echo '🤖 Modèle: '.($response->aiDetails?->model ?? 'N/A')."\n";
        echo '📊 Confiance: '.($response->aiDetails?->confidence ?? 0)."\n";
    } else {
        echo "❌ ÉCHEC: Aucune réponse générée\n";
        if ($response) {
            echo '   Erreur: '.($response->processingError ?? 'Inconnue')."\n";
        }
    }

} catch (\Exception $e) {
    echo '❌ ERREUR: '.$e->getMessage()."\n";
    echo '   Fichier: '.$e->getFile().':'.$e->getLine()."\n";
}

echo "\n🏁 Test terminé\n";
