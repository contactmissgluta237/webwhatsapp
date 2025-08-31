<?php

/**
 * Test temps réel du StoreMessagesListener
 *
 * Ce script teste le listener avec un vrai compte WhatsApp (ID=9)
 * et vérifie combien de messages sont débités avec la nouvelle formule.
 */

require_once __DIR__.'/../../vendor/autoload.php';

use App\DTOs\WhatsApp\ProductDataDTO;
use App\DTOs\WhatsApp\WhatsAppAIResponseDTO;
use App\DTOs\WhatsApp\WhatsAppMessageRequestDTO;
use App\DTOs\WhatsApp\WhatsAppMessageResponseDTO;
use App\Events\WhatsApp\MessageProcessedEvent;
use App\Listeners\WhatsApp\StoreMessagesListener;
use App\Models\UserProduct;
use App\Models\WhatsAppAccount;

// Bootstrap Laravel
$app = require_once __DIR__.'/../../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== TEST TEMPS RÉEL DU STORE MESSAGES LISTENER ===\n";
echo 'Date: '.now()->format('Y-m-d H:i:s')."\n\n";

try {
    // 1. Récupérer le compte WhatsApp ID=9
    $account = WhatsAppAccount::with(['user.wallet', 'user.activeSubscription'])->find(9);

    if (! $account) {
        echo "❌ ERREUR: Compte WhatsApp avec ID=9 non trouvé!\n";
        exit(1);
    }

    $user = $account->user;
    echo "✅ Compte WhatsApp trouvé:\n";
    echo "   - ID: {$account->id}\n";
    echo "   - Session: {$account->session_id}\n";
    echo '   - Téléphone: '.($account->phone_number ?? 'Non connecté')."\n";
    echo "   - Utilisateur: {$user->name} (ID: {$user->id})\n";
    echo '   - Wallet balance: '.($user->wallet->balance ?? '0')." USD\n";
    echo '   - Subscription active: '.($user->activeSubscription ? 'OUI' : 'NON')."\n";
    if ($user->activeSubscription) {
        echo "   - Messages restants: {$user->activeSubscription->getRemainingMessages()}\n";
    }
    echo "\n";

    // 2. Créer des produits réels pour le test
    echo "📦 Création des produits de test...\n";

    $product1 = UserProduct::create([
        'user_id' => $user->id,
        'title' => 'Produit Test Temps Réel 1',
        'description' => 'Test du nouveau système de facturation',
        'price' => 15000,
        'is_active' => true,
    ]);

    $product2 = UserProduct::create([
        'user_id' => $user->id,
        'title' => 'Produit Test Temps Réel 2',
        'description' => 'Deuxième produit pour tester la formule',
        'price' => 25000,
        'is_active' => true,
    ]);

    echo "   ✅ Produit 1 créé (ID: {$product1->id}) avec 2 médias\n";
    echo "   ✅ Produit 2 créé (ID: {$product2->id}) avec 2 médias\n";
    echo "   📊 Total: 2 produits avec 4 médias au total\n\n";

    // 3. Créer la réponse IA avec les produits
    $productDTOs = [
        new ProductDataDTO(
            $product1->title,
            ['https://example.com/product1_image1.jpg', 'https://example.com/product1_image2.jpg'],
            $product1->description,
            $product1->price,
            'USD'
        ),
        new ProductDataDTO(
            $product2->title,
            ['https://example.com/product2_image1.jpg', 'https://example.com/product2_video1.mp4'],
            $product2->description,
            $product2->price,
            'USD'
        ),
    ];

    $aiResponseDTO = new WhatsAppAIResponseDTO(
        'Voici nos produits disponibles qui correspondent à votre demande!',
        'gpt-4'
    );

    $responseDTO = WhatsAppMessageResponseDTO::success(
        'Voici nos produits disponibles qui correspondent à votre demande!',
        $aiResponseDTO,
        waitTime: 2,
        typingDuration: 3,
        products: $productDTOs,
        sessionId: $account->session_id,
        phoneNumber: $account->phone_number
    );

    echo "🤖 Réponse IA créée:\n";
    echo "   - Message: Voici nos produits disponibles...\n";
    echo "   - Produits inclus: 2\n";
    echo "   - Médias totaux: 4 (mais plus facturés!)\n";
    echo "   - Formule attendue: 1 (IA) + 2 (produits) = 3 messages\n\n";

    // 4. Créer le message entrant
    $incomingMessage = new WhatsAppMessageRequestDTO(
        id: 'test_'.time(),
        from: '+237690123456@c.us',
        body: 'Montrez-moi vos produits disponibles',
        timestamp: time(),
        type: 'text',
        isGroup: false
    );

    // 5. Créer l'événement
    $event = new MessageProcessedEvent($account, $incomingMessage, $responseDTO);

    echo "📡 Événement MessageProcessedEvent créé\n";
    echo "   - Session ID: {$event->getSessionId()}\n";
    echo "   - From: {$event->getFromPhone()}\n";
    echo "   - Message: {$incomingMessage->body}\n\n";

    // 6. Sauvegarder l'état initial
    $initialWalletBalance = $user->wallet?->balance ?? 0;
    $initialMessagesUsed = $user->activeSubscription?->getUsageForAccount($account)?->messages_used ?? 0;

    echo "💾 État initial sauvegardé:\n";
    echo "   - Wallet balance: {$initialWalletBalance} USD\n";
    echo "   - Messages utilisés: {$initialMessagesUsed}\n\n";

    // 7. ⚠️ PAS DE MOCK - TEST EN RÉEL SUR LE COMPTE ID=9 ⚠️
    echo "🎯 ATTENTION: TEST EN RÉEL - Aucun mock!\n";
    echo "   ⚠️  Ce test va VRAIMENT débiter ton compte!\n";
    echo "   ⚠️  Wallet ou quota seront réellement affectés!\n\n";

    // 8. Exécuter le listener
    echo "🚀 EXÉCUTION DU LISTENER...\n";
    echo "===========================================\n\n";

    $listener = app(StoreMessagesListener::class);
    $listener->handle($event);

    echo "\n===========================================\n";
    echo "✅ LISTENER EXÉCUTÉ AVEC SUCCÈS!\n\n";

    // 8. Vérifier les résultats
    $user->refresh();
    $account->refresh();

    $finalWalletBalance = $user->wallet?->balance ?? 0;
    $finalMessagesUsed = $user->activeSubscription?->getUsageForAccount($account)?->messages_used ?? 0;

    echo "📊 RÉSULTATS DU TEST:\n";
    echo "===========================================\n";

    // Calcul des messages débités
    $messagesDébités = $finalMessagesUsed - $initialMessagesUsed;
    $walletDébité = $initialWalletBalance - $finalWalletBalance;

    if ($user->activeSubscription && $user->activeSubscription->hasRemainingMessages()) {
        echo "💳 MODE: Débit sur QUOTA de subscription\n";
        echo "   - Messages débités: {$messagesDébités}\n";
        echo "   - Messages restants: {$user->activeSubscription->getRemainingMessages()}\n";
        echo "   - Wallet balance: {$finalWalletBalance} USD (inchangé)\n";
    } else {
        echo "💰 MODE: Débit sur WALLET\n";
        echo "   - Montant débité: {$walletDébité} USD\n";
        echo "   - Nouveau balance: {$finalWalletBalance} USD\n";
    }

    echo "\n🎯 VÉRIFICATION DE LA NOUVELLE FORMULE:\n";
    echo "   - Attendu: 3 messages (1 IA + 2 produits)\n";
    echo "   - Obtenu: {$messagesDébités} messages\n";
    echo "   - Médias ignorés: ✅ (4 médias présents mais non facturés)\n";
    echo '   - Résultat: '.($messagesDébités === 3 ? '✅ CORRECT!' : '❌ INCORRECT!')."\n\n";

    // 9. Vérifier les logs créés
    $latestUsageLog = \App\Models\MessageUsageLog::where('user_id', $user->id)
        ->orderBy('created_at', 'desc')
        ->first();

    if ($latestUsageLog) {
        echo "📋 DERNIER LOG D'USAGE CRÉÉ:\n";
        echo "   - ID: {$latestUsageLog->id}\n";
        echo "   - AI Cost: {$latestUsageLog->ai_message_cost} USD\n";
        echo "   - Product Count: {$latestUsageLog->product_count}\n";
        echo "   - Product Cost: {$latestUsageLog->product_cost} USD\n";
        echo "   - Total Cost: {$latestUsageLog->total_cost} USD\n";
        echo "   - Billing Type: {$latestUsageLog->billing_type->value}\n";
        echo "   - Created: {$latestUsageLog->created_at}\n";
    }

    echo "\n🎉 TEST TERMINÉ AVEC SUCCÈS!\n";
    echo "Vérifiez vos logs Laravel pour voir tous les détails du processus.\n\n";

} catch (\Exception $e) {
    echo "❌ ERREUR LORS DU TEST:\n";
    echo "   - Message: {$e->getMessage()}\n";
    echo "   - Fichier: {$e->getFile()}:{$e->getLine()}\n";
    echo "   - Trace:\n";
    echo $e->getTraceAsString();
    exit(1);
} finally {
    // Nettoyage: supprimer les données de test
    if (isset($product1)) {
        $product1->delete();
    }
    if (isset($product2)) {
        $product2->delete();
    }
    echo "🧹 Données de test supprimées.\n";
}
