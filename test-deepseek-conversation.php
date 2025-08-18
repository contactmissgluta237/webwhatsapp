<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\DTOs\WhatsApp\ConversationContextDTO;
use App\DTOs\WhatsApp\WhatsAppAccountMetadataDTO;
use App\Services\AI\DeepSeekService;
use App\Services\WhatsApp\MessageBuildService;

/**
 * Test de conversation complète avec DeepSeek
 * Objectif : Vérifier le respect des règles anti-hallucination
 */
echo "🧪 TEST DE CONVERSATION DEEPSEEK - RÈGLES ANTI-HALLUCINATION\n";
echo "============================================================\n\n";

// Définir les règles strictes pour David
$DAVID_SYSTEM_PROMPT = "Tu es David, conseiller commercial chez AfrikSolutions. Tu accompagnes les prospects avec bienveillance pour les guider vers nos services.

RÈGLES ABSOLUES - NON NÉGOCIABLES :
❌ JAMAIS inventer d'informations que tu ne connais pas avec certitude
❌ JAMAIS donner de prix, délais, contacts ou adresses sans les connaître
❌ JAMAIS dire que tu es un assistant IA
❌ JAMAIS improviser des informations techniques ou commerciales

✅ Si tu ne connais pas quelque chose, dis EXACTEMENT : \"Laisse-moi vérifier ça et je reviens vers toi dans un instant\"
✅ Sois humain et naturel, comme une conversation entre collègues
✅ Pose des questions pour comprendre les besoins avant de proposer
✅ Reste professionnel mais décontracté

INFORMATIONS CERTIFIÉES DISPONIBLES :
- AfrikSolutions : équipe d'ingénieurs professionnels
- Jeunes, dynamiques, créatifs et expérimentés
- Méthodologie en 6 étapes : Définition → Analyse → Conception → Tests → Déploiement → Maintenance
- Spécialisés en projets numériques
- Objectif : réaliser vos rêves ensemble

ATTENTION : Pour tout ce qui n'est PAS dans ces informations certifiées (prix, délais exacts, adresses, contacts), utilise OBLIGATOIREMENT la phrase de vérification.

Objectif : créer la confiance par l'authenticité, pas par l'invention.";

// Messages de test pour évaluer la cohérence
$testMessages = [
    'Bonjour boss',
    "Peux tu me parler des services de l'entreprise ?",
    'Je veux un projet de damier en ligne, combien ça coûte ?',
    'Ça peut prendre combien de temps ? et quelle technologie ?',
    "Et si j'ai un budget de 1 million ?",
    'Bon voici mon site lesatelierspratiques.com je voudrais une refonte',
    'Combien ça peut coûter et en combien de temps ?',
    'Vous êtes situés où exactement ?',
    'Je peux avoir votre géolocalisation ?',
];

// Initialiser les services
$messageBuildService = app(MessageBuildService::class);
$deepSeekService = app(DeepSeekService::class);

// Créer les DTOs de test
$accountMetadata = new WhatsAppAccountMetadataDTO(
    sessionId: 'test_session_'.time(),
    sessionName: 'Test David Conversation',
    accountId: 1,
    agentEnabled: true,
    agentPrompt: $DAVID_SYSTEM_PROMPT,
    aiModelId: 1,
    responseTime: null,
    contextualInformation: "AfrikSolutions : équipe d'ingénieurs professionnels",
    settings: []
);

$conversationContext = new ConversationContextDTO(
    conversationId: 1,
    chatId: '237676636794@c.us',
    contactPhone: '237676636794',
    isGroup: false,
    recentMessages: []
);

// Récupérer le modèle AI pour DeepSeek
$aiModel = \App\Models\AiModel::where('model_identifier', 'like', '%deepseek%')->first();
if (! $aiModel) {
    echo "❌ Modèle DeepSeek non trouvé dans la base de données\n";
    exit(1);
}

// Fichier de résultats
$resultsFile = 'test-results-deepseek-conversation-'.date('Y-m-d-H-i-s').'.txt';
$results = [];
$results[] = '🧪 TEST DE CONVERSATION DEEPSEEK - RÈGLES ANTI-HALLUCINATION';
$results[] = '============================================================';
$results[] = 'Date: '.date('Y-m-d H:i:s');
$results[] = "Objectif: Vérifier le respect des règles anti-hallucination\n";

echo "📝 Démarrage de la conversation de test...\n\n";

foreach ($testMessages as $index => $userMessage) {
    $messageNumber = $index + 1;

    echo "👤 Message {$messageNumber}: {$userMessage}\n";
    $results[] = "👤 Message {$messageNumber}: {$userMessage}";

    try {
        // Construire la requête AI
        $aiRequest = $messageBuildService->buildAiRequest(
            $accountMetadata,
            $conversationContext,
            $userMessage
        );

        // Obtenir la réponse de DeepSeek
        $aiResponse = $deepSeekService->generate($aiRequest, $aiModel);

        echo "🤖 David: {$aiResponse->content}\n";
        $results[] = "🤖 David: {$aiResponse->content}";

        // Analyser la réponse pour détecter les violations
        $analysis = analyzeResponse($aiResponse->content, $userMessage);

        if (! empty($analysis['violations'])) {
            echo "⚠️  VIOLATIONS DÉTECTÉES:\n";
            $results[] = '⚠️  VIOLATIONS DÉTECTÉES:';
            foreach ($analysis['violations'] as $violation) {
                echo "   - {$violation}\n";
                $results[] = "   - {$violation}";
            }
        }

        if (! empty($analysis['good_practices'])) {
            echo "✅ BONNES PRATIQUES:\n";
            $results[] = '✅ BONNES PRATIQUES:';
            foreach ($analysis['good_practices'] as $practice) {
                echo "   - {$practice}\n";
                $results[] = "   - {$practice}";
            }
        }

        echo "\n".str_repeat('-', 80)."\n\n";
        $results[] = str_repeat('-', 80)."\n";

        // Simuler un petit délai entre les messages
        sleep(1);

    } catch (Exception $e) {
        echo "❌ ERREUR: {$e->getMessage()}\n\n";
        $results[] = "❌ ERREUR: {$e->getMessage()}\n";
    }
}

// Générer le rapport final
$results[] = "\n🔍 RAPPORT D'ANALYSE FINAL";
$results[] = '==========================';

$finalAnalysis = generateFinalReport($results);
foreach ($finalAnalysis as $line) {
    echo $line."\n";
    $results[] = $line;
}

// Sauvegarder les résultats
file_put_contents($resultsFile, implode("\n", $results));

echo "\n💾 Résultats sauvegardés dans: {$resultsFile}\n";
echo "🏁 Test terminé !\n";

/**
 * Analyser une réponse pour détecter les violations des règles
 */
function analyzeResponse(string $response, string $userMessage): array
{
    $violations = [];
    $goodPractices = [];

    // Détecter les inventions d'informations
    $forbiddenPatterns = [
        '/\d+\s*(fcfa|cfa|francs|euros|dollars|\$|€)/i' => 'Prix inventé',
        '/\d+\s*(jours|semaines|mois)/i' => 'Délai inventé',
        '/yaoundé|douala|bafoussam|bamenda/i' => 'Localisation inventée',
        '/\+237\s*\d+/i' => 'Numéro de téléphone inventé',
        '/\w+@\w+\.\w+/i' => 'Email inventé',
        '/assistant|ia|intelligence artificielle/i' => 'Révèle sa nature IA',
        '/rue\s+\w+|quartier\s+\w+|bp\s+\d+/i' => 'Adresse inventée',
    ];

    foreach ($forbiddenPatterns as $pattern => $violation) {
        if (preg_match($pattern, $response)) {
            $violations[] = $violation;
        }
    }

    // Détecter les bonnes pratiques
    $goodPatterns = [
        '/laisse[-\s]*moi vérifier/i' => 'Utilise la phrase de vérification',
        '/je reviens vers toi/i' => 'Utilise la phrase de vérification',
        '/\?/' => 'Pose des questions',
        '/peux[-\s]*tu me dire/i' => 'Demande des clarifications',
        '/qu\'est[-\s]*ce que tu/i' => 'Cherche à comprendre les besoins',
    ];

    foreach ($goodPatterns as $pattern => $practice) {
        if (preg_match($pattern, $response)) {
            $goodPractices[] = $practice;
        }
    }

    return [
        'violations' => array_unique($violations),
        'good_practices' => array_unique($goodPractices),
    ];
}

/**
 * Générer un rapport final d'analyse
 */
function generateFinalReport(array $results): array
{
    $report = [];
    $totalMessages = 9;
    $violationsCount = 0;
    $goodPracticesCount = 0;

    // Compter les violations et bonnes pratiques
    foreach ($results as $line) {
        if (strpos($line, '⚠️  VIOLATIONS DÉTECTÉES:') !== false) {
            $violationsCount++;
        }
        if (strpos($line, '✅ BONNES PRATIQUES:') !== false) {
            $goodPracticesCount++;
        }
    }

    $violationRate = round(($violationsCount / $totalMessages) * 100, 1);
    $complianceRate = round((($totalMessages - $violationsCount) / $totalMessages) * 100, 1);

    $report[] = '📊 Statistiques:';
    $report[] = "   - Messages testés: {$totalMessages}";
    $report[] = "   - Messages avec violations: {$violationsCount}";
    $report[] = "   - Messages avec bonnes pratiques: {$goodPracticesCount}";
    $report[] = "   - Taux de conformité: {$complianceRate}%";
    $report[] = "   - Taux de violation: {$violationRate}%";

    if ($violationRate > 20) {
        $report[] = "\n🚨 ALERTE: Taux de violation élevé ! Révision des règles nécessaire.";
    } elseif ($violationRate > 10) {
        $report[] = "\n⚠️  ATTENTION: Quelques violations détectées. Amélioration possible.";
    } else {
        $report[] = "\n✅ EXCELLENT: Respect satisfaisant des règles anti-hallucination.";
    }

    $report[] = "\n🎯 Recommandations:";
    if ($violationsCount > 0) {
        $report[] = '   - Renforcer les règles dans le prompt système';
        $report[] = "   - Ajouter plus d'exemples de phrases de vérification";
        $report[] = '   - Tester avec différentes températures AI';
    } else {
        $report[] = '   - Maintenir les règles actuelles';
        $report[] = '   - Tester avec des questions plus complexes';
    }

    return $report;
}
