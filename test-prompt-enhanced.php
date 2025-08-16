<?php

declare(strict_types=1);

/**
 * Test d'amélioration de prompt WhatsApp
 * 
 * Usage: php test-prompt-enhanced.php
 * 
 * Modifiez la variable $originalPrompt ci-dessous pour tester
 * différents prompts et voir les résultats d'amélioration.
 */

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Configuration du prompt à tester - MODIFIEZ ICI
$originalPrompt = "Tu es un assistant pour Afrik Solutions qui aide les clients avec leurs questions";

echo "\n";
echo "🧪 TEST D'AMÉLIORATION DE PROMPT WHATSAPP";
echo "\n" . str_repeat("=", 60) . "\n";

try {
    // Récupération du premier compte WhatsApp
    $account = \App\Models\WhatsAppAccount::first();
    
    if (!$account) {
        echo "❌ Erreur: Aucun compte WhatsApp trouvé dans la base de données\n";
        echo "   Veuillez d'abord créer un compte WhatsApp ou lancer les seeders\n";
        exit(1);
    }

    echo "📱 Compte WhatsApp: {$account->name} (ID: {$account->id})\n";
    echo "🤖 Modèle IA configuré: " . ($account->aiModel ? $account->aiModel->name : 'Défaut') . "\n";
    echo "\n";

    // Affichage du prompt original
    echo "📝 PROMPT ORIGINAL:\n";
    echo str_repeat("-", 40) . "\n";
    echo $originalPrompt . "\n";
    echo str_repeat("-", 40) . "\n";
    echo "Longueur: " . strlen($originalPrompt) . " caractères\n\n";

    // Lancement de l'amélioration
    echo "🚀 Amélioration en cours...\n";
    $startTime = microtime(true);
    
    $service = app(\App\Contracts\PromptEnhancementInterface::class);
    $enhancedPrompt = $service->enhancePrompt($account, $originalPrompt);
    
    $endTime = microtime(true);
    $duration = round($endTime - $startTime, 2);

    echo "✅ Amélioration terminée en {$duration}s\n\n";

    // Affichage du prompt amélioré
    echo "✨ PROMPT AMÉLIORÉ:\n";
    echo str_repeat("=", 60) . "\n";
    echo $enhancedPrompt . "\n";
    echo str_repeat("=", 60) . "\n";

    // Statistiques de nettoyage
    echo "\n📊 STATISTIQUES:\n";
    echo "• Longueur originale: " . strlen($originalPrompt) . " caractères\n";
    echo "• Longueur améliorée: " . strlen($enhancedPrompt) . " caractères\n";
    echo "• Gain: " . (strlen($enhancedPrompt) - strlen($originalPrompt)) . " caractères\n";
    echo "• Durée: {$duration}s\n";

    // Vérifications de qualité
    echo "\n🔍 VÉRIFICATIONS QUALITÉ:\n";
    
    $hasStars = substr_count($enhancedPrompt, '*') > 0;
    $hasEmojis = preg_match('/[✅❌🔥💡📱⚡]/u', $enhancedPrompt);
    $hasTitle = stripos($enhancedPrompt, 'prompt') !== false;
    $hasSections = preg_match('/^(Rôle|Comportement|Gestion|Interdits|Exemple|Note)\s*:/m', $enhancedPrompt);
    $hasMarkdown = preg_match('/\*\*[^*]+\*\*/', $enhancedPrompt);

    echo "• Étoiles supprimées: " . ($hasStars ? "❌ NON" : "✅ OUI") . "\n";
    echo "• Emojis supprimés: " . ($hasEmojis ? "❌ NON" : "✅ OUI") . "\n";
    echo "• Titre supprimé: " . ($hasTitle ? "❌ NON" : "✅ OUI") . "\n";
    echo "• Sections supprimées: " . ($hasSections ? "❌ NON" : "✅ OUI") . "\n";
    echo "• Markdown supprimé: " . ($hasMarkdown ? "❌ NON" : "✅ OUI") . "\n";

    $qualityScore = 0;
    if (!$hasStars) $qualityScore++;
    if (!$hasEmojis) $qualityScore++;
    if (!$hasTitle) $qualityScore++;
    if (!$hasSections) $qualityScore++;
    if (!$hasMarkdown) $qualityScore++;

    echo "\n🎯 SCORE QUALITÉ: {$qualityScore}/5 ";
    if ($qualityScore === 5) {
        echo "🎉 PARFAIT";
    } elseif ($qualityScore >= 3) {
        echo "👍 BON";
    } else {
        echo "⚠️ À AMÉLIORER";
    }
    echo "\n";

    // Instructions pour modifier le test
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "💡 POUR TESTER UN AUTRE PROMPT:\n";
    echo "   1. Modifiez la variable \$originalPrompt ligne 17\n";
    echo "   2. Relancez: php test-prompt-enhanced.php\n";
    echo str_repeat("=", 60) . "\n";

} catch (\Exception $e) {
    echo "\n❌ ERREUR LORS DE L'AMÉLIORATION:\n";
    echo "   " . $e->getMessage() . "\n";
    echo "\n🔧 Vérifiez:\n";
    echo "   • La base de données est accessible\n";
    echo "   • Les modèles IA sont configurés\n";
    echo "   • Les clés API sont valides\n";
    echo "   • La connexion internet fonctionne\n\n";
    exit(1);
}

echo "\n";
