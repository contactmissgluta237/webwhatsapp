#!/usr/bin/env php
<?php

require_once __DIR__.'/vendor/autoload.php';

use Illuminate\Support\Facades\Http;

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🔍 Test de connexion Ollama...\n\n";

$ollamaUrl = 'http://209.126.83.125:11434';

// Test 1: Version
echo "1. Test version API...\n";
try {
    $response = Http::timeout(10)->connectTimeout(5)->get($ollamaUrl.'/api/version');
    if ($response->successful()) {
        $data = $response->json();
        echo '✅ Version: '.($data['version'] ?? 'inconnue')."\n";
    } else {
        echo '❌ Erreur: '.$response->status()."\n";
    }
} catch (Exception $e) {
    echo '❌ Exception: '.$e->getMessage()."\n";
}

echo "\n";

// Test 2: Modèles disponibles
echo "2. Test modèles disponibles...\n";
try {
    $response = Http::timeout(10)->connectTimeout(5)->get($ollamaUrl.'/api/tags');
    if ($response->successful()) {
        $data = $response->json();
        $models = $data['models'] ?? [];
        echo '✅ Modèles trouvés: '.count($models)."\n";
        foreach ($models as $model) {
            echo '  - '.$model['name']."\n";
        }
    } else {
        echo '❌ Erreur: '.$response->status()."\n";
    }
} catch (Exception $e) {
    echo '❌ Exception: '.$e->getMessage()."\n";
}

echo "\n";

// Test 3: Chat simple
echo "3. Test chat API simple...\n";
try {
    $payload = [
        'model' => 'gemma2:2b',
        'messages' => [
            [
                'role' => 'user',
                'content' => 'Hello! Just say OK to confirm you work.',
            ],
        ],
        'stream' => false,
    ];

    $response = Http::timeout(60)
        ->connectTimeout(10)
        ->post($ollamaUrl.'/api/chat', $payload);

    if ($response->successful()) {
        $data = $response->json();
        $content = $data['message']['content'] ?? 'Pas de contenu';
        echo '✅ Réponse: '.trim($content)."\n";
        echo '✅ Durée totale: '.($data['total_duration'] ?? 0) / 1000000 ."ms\n";
    } else {
        echo '❌ Erreur: '.$response->status().' - '.$response->body()."\n";
    }
} catch (Exception $e) {
    echo '❌ Exception: '.$e->getMessage()."\n";
}

echo "\n";

// Test 4: Conversation cohérente avec contexte métier
echo "4. Test de conversation cohérente avec contexte métier...\n";
echo "═══════════════════════════════════════════════════════════════════\n\n";

try {
    $model = 'gemma2:2b';

    // CONTEXTE MÉTIER - comme dans ton WhatsAppAccount
    $agentPrompt = 'Tu es un assistant de vente de téléphones. Tu es professionnel et chaleureux.';
    $contextualInfo = 'Nous vendons des téléphones. Nos produits: Google Pixel 6 en promotion à 100mille FCFA, Pixel 9 neuf scellé à 900mille FCFA, iPhone XR d\'occasion à 90mille FCFA. Garantie 1 an sur tous nos produits.';

    // Initialiser le contexte avec le système prompt
    $context = [
        [
            'role' => 'system',
            'content' => $agentPrompt."\n\nInformations sur nos produits:\n".$contextualInfo,
        ],
    ];

    // Premier message : salut
    echo "┌─ MESSAGE 1 ─────────────────────────────────────────────────────┐\n";
    $userMessage1 = 'Bonjour boss';
    echo '│ 👤 USER: '.$userMessage1."\n";
    echo "└─────────────────────────────────────────────────────────────────┘\n";

    $payload = [
        'model' => $model,
        'messages' => array_merge($context, [
            ['role' => 'user', 'content' => $userMessage1],
        ]),
        'stream' => false,
    ];

    $response = Http::timeout(60)->connectTimeout(10)->post($ollamaUrl.'/api/chat', $payload);

    if (! $response->successful()) {
        throw new Exception('Erreur premier message: '.$response->status());
    }

    $firstData = $response->json();
    $firstResponse = $firstData['message']['content'] ?? '';

    echo "┌─ RÉPONSE 1 ─────────────────────────────────────────────────────┐\n";
    echo '│ 🤖 IA: '.trim($firstResponse)."\n";
    echo "└─────────────────────────────────────────────────────────────────┘\n\n";

    // Vérifier la réponse contient un salut
    $responseText = strtolower($firstResponse);
    $hasGreeting = str_contains($responseText, 'bonjour') ||
                   str_contains($responseText, 'salut') ||
                   str_contains($responseText, 'hello');

    if ($hasGreeting) {
        echo "✅ Salut détecté dans la première réponse\n";
    } else {
        echo "⚠️ Aucun salut détecté dans la première réponse\n";
    }

    // Construire le contexte pour le message suivant
    $context[] = ['role' => 'user', 'content' => $userMessage1];
    $context[] = ['role' => 'assistant', 'content' => $firstResponse];

    // Deuxième message : demande sur les téléphones
    echo "\n┌─ MESSAGE 2 ─────────────────────────────────────────────────────┐\n";
    $userMessage2 = 'Quels sont les téléphones que vous vendez ?';
    echo '│ 👤 USER: '.$userMessage2."\n";
    echo "└─────────────────────────────────────────────────────────────────┘\n";

    $payload = [
        'model' => $model,
        'messages' => array_merge($context, [
            ['role' => 'user', 'content' => $userMessage2],
        ]),
        'stream' => false,
    ];

    $response = Http::timeout(60)->connectTimeout(10)->post($ollamaUrl.'/api/chat', $payload);

    if (! $response->successful()) {
        throw new Exception('Erreur deuxième message: '.$response->status());
    }

    $secondData = $response->json();
    $secondResponse = $secondData['message']['content'] ?? '';

    echo "┌─ RÉPONSE 2 ─────────────────────────────────────────────────────┐\n";
    echo '│ 🤖 IA: '.trim($secondResponse)."\n";
    echo "└─────────────────────────────────────────────────────────────────┘\n\n";

    $responseText2 = strtolower($secondResponse);

    // Vérifier mentions des téléphones du contexte métier
    $hasPixel = str_contains($responseText2, 'pixel');
    $hasIphone = str_contains($responseText2, 'iphone');
    $hasPixel6 = str_contains($responseText2, 'pixel 6');
    $hasPixel9 = str_contains($responseText2, 'pixel 9');
    $hasIphoneXR = str_contains($responseText2, 'iphone xr');

    echo "Produits mentionnés:\n";
    echo '  - Pixel: '.($hasPixel ? '✅' : '❌')."\n";
    echo '  - iPhone: '.($hasIphone ? '✅' : '❌')."\n";
    echo '  - Pixel 6 spécifique: '.($hasPixel6 ? '✅' : '❌')."\n";
    echo '  - Pixel 9 spécifique: '.($hasPixel9 ? '✅' : '❌')."\n";
    echo '  - iPhone XR spécifique: '.($hasIphoneXR ? '✅' : '❌')."\n";

    if ($hasPixel && $hasIphone) {
        echo "✅ Téléphones principaux mentionnés (iPhone et Pixel)\n";
    } else {
        echo "⚠️ Téléphones pas tous mentionnés\n";
    }

    // Vérifier que l'IA ne répète plus les saluts
    $hasGreetingAgain = str_contains($responseText2, 'bonjour') ||
                        str_contains($responseText2, 'salut') ||
                        str_contains($responseText2, 'hello');

    if (! $hasGreetingAgain) {
        echo "✅ Plus de saluts répétés (cohérence conversationnelle)\n";
    } else {
        echo "❌ L'IA répète encore des saluts (manque de cohérence)\n";
    }

    // Construire le contexte pour le 3ème message
    $context[] = ['role' => 'user', 'content' => $userMessage2];
    $context[] = ['role' => 'assistant', 'content' => $secondResponse];

    // Troisième message : prix spécifique du Pixel 6
    echo "\n┌─ MESSAGE 3 ─────────────────────────────────────────────────────┐\n";
    $userMessage3 = 'C\'est quoi le prix du Pixel 6 svp ?';
    echo '│ 👤 USER: '.$userMessage3."\n";
    echo "└─────────────────────────────────────────────────────────────────┘\n";

    $payload = [
        'model' => $model,
        'messages' => array_merge($context, [
            ['role' => 'user', 'content' => $userMessage3],
        ]),
        'stream' => false,
    ];

    $response = Http::timeout(60)->connectTimeout(10)->post($ollamaUrl.'/api/chat', $payload);

    if (! $response->successful()) {
        throw new Exception('Erreur troisième message: '.$response->status());
    }

    $thirdData = $response->json();
    $thirdResponse = $thirdData['message']['content'] ?? '';

    echo "┌─ RÉPONSE 3 ─────────────────────────────────────────────────────┐\n";
    echo '│ 🤖 IA: '.trim($thirdResponse)."\n";
    echo "└─────────────────────────────────────────────────────────────────┘\n\n";

    // Vérifier mention du prix exact du contexte (100mille FCFA pour Pixel 6)
    $responseText3 = strtolower($thirdResponse);
    $hasCorrectPrice = str_contains($responseText3, '100') &&
                       (str_contains($responseText3, 'mille') || str_contains($responseText3, '000'));

    $hasFCFA = str_contains($responseText3, 'fcfa');
    $hasPromo = str_contains($responseText3, 'promotion') || str_contains($responseText3, 'promo');

    echo "Vérifications prix Pixel 6:\n";
    echo '  - Prix 100 mille/000: '.($hasCorrectPrice ? '✅' : '❌')."\n";
    echo '  - Monnaie FCFA: '.($hasFCFA ? '✅' : '❌')."\n";
    echo '  - Mention promotion: '.($hasPromo ? '✅' : '❌')."\n";

    if ($hasCorrectPrice) {
        echo "✅ Prix cohérent mentionné (100 mille du contexte métier)\n";
    } else {
        echo "❌ Prix INCORRECT - devrait mentionner 100mille du contexte métier\n";
    }

    // Vérifier que l'IA ne répète toujours pas les saluts au 3ème message
    $hasGreetingThird = str_contains($responseText3, 'bonjour') ||
                        str_contains($responseText3, 'salut') ||
                        str_contains($responseText3, 'hello');

    if (! $hasGreetingThird) {
        echo "✅ Toujours pas de saluts répétés au 3ème message\n";
    } else {
        echo "⚠️ L'IA répète encore des saluts au 3ème message\n";
    }

    // Résumé du test de conversation avec contexte métier
    echo "\n═══════════════════════════════════════════════════════════════════\n";
    echo "📊 RÉSUMÉ DE LA CONVERSATION AVEC CONTEXTE MÉTIER:\n";
    echo "═══════════════════════════════════════════════════════════════════\n";
    echo '- Salut initial reconnu: '.($hasGreeting ? '✅' : '❌')."\n";
    echo '- Plus de saluts répétés (2ème): '.(! $hasGreetingAgain ? '✅' : '❌')."\n";
    echo '- Plus de saluts répétés (3ème): '.(! $hasGreetingThird ? '✅' : '❌')."\n";
    echo '- Téléphones du contexte mentionnés: '.($hasPixel && $hasIphone ? '✅' : '❌')."\n";
    echo '- Prix EXACT du contexte métier: '.($hasCorrectPrice ? '✅' : '❌')."\n";
    echo '- Monnaie FCFA du contexte: '.($hasFCFA ? '✅' : '❌')."\n";

    $conversationCoherent = $hasGreeting && ! $hasGreetingAgain && ! $hasGreetingThird &&
                           $hasPixel && $hasIphone && $hasCorrectPrice && $hasFCFA;

    echo "═══════════════════════════════════════════════════════════════════\n";
    if ($conversationCoherent) {
        echo "🎉 RÉSULTAT: SUCCÈS COMPLET - IA UTILISE BIEN LE CONTEXTE MÉTIER\n";
    } else {
        echo "⚠️ RÉSULTAT: PARTIEL - Problèmes avec le contexte métier détectés\n";
    }
    echo "═══════════════════════════════════════════════════════════════════\n";

} catch (Exception $e) {
    echo '❌ Exception conversation: '.$e->getMessage()."\n";
}

echo "\n🎯 Tous les tests terminés.\n";
