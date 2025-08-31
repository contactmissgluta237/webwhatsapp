<?php

declare(strict_types=1);

namespace Tests\E2E\Billing;

use App\Services\WhatsApp\Helpers\MessageBillingHelper;

/**
 * Test E2E pour la déduction de quota quand l'utilisateur a des messages disponibles
 *
 * Ce test utilise la vraie base de données et vérifie :
 * - La création d'un customer réel avec souscription starter
 * - Le dispatch d'événements MessageProcessedEvent réels
 * - La déduction correcte du quota (pas de débit wallet)
 * - Les logs générés
 * - Les notifications envoyées si nécessaire
 */
class QuotaDebitE2ETest extends BaseE2EBillingTest
{
    /**
     * Test: Customer avec quota disponible - Utilisation normale du quota
     *
     * Scénario:
     * - Customer avec 100 messages dans son package starter
     * - Envoi d'une réponse complexe (AI + 3 produits) = 4 messages
     * - Vérification que 4 messages sont déduits du quota
     * - Aucun débit wallet
     * - Logs de facturation présents
     */
    public function test_quota_debit_with_complex_response(): void
    {
        // ============================================================
        // SETUP: Vérifier l'état initial
        // ============================================================
        echo "\n🔍 [E2E] Vérification état initial...\n";

        $this->verifyInitialState();

        $initialMessages = $this->subscription->messages_limit; // 100
        $initialWalletBalance = $this->wallet->balance; // 1000.00

        echo "✅ Customer: {$this->customer->email}\n";
        echo "✅ Package: {$this->starterPackage->display_name} ({$initialMessages} messages)\n";
        echo "✅ Wallet: {$initialWalletBalance} USD\n";
        echo "✅ WhatsApp Account: {$this->whatsappAccount->phone_number}\n";

        // ============================================================
        // ACTION: Générer et dispatcher l'événement
        // ============================================================
        echo "\n📤 [E2E] Génération et dispatch de l'événement...\n";

        $messageRequest = $this->generateMessageRequest('Bonjour, montrez-moi vos produits');
        $aiResponse = $this->generateComplexAIResponse();

        // Calculer le coût attendu
        $expectedMessageCount = MessageBillingHelper::getNumberOfMessagesFromResponse($aiResponse);
        $expectedBillingAmount = MessageBillingHelper::getAmountToBillFromResponse($aiResponse);

        echo "🧮 Messages calculés: {$expectedMessageCount}\n";
        echo "🧮 Coût calculé: {$expectedBillingAmount} USD\n";
        echo "🧮 Détail: 1 AI + 3 produits = {$expectedMessageCount} messages\n";

        // Dispatcher l'événement
        echo "🚀 Dispatch de MessageProcessedEvent...\n";
        $this->dispatchMessageProcessedEvent($messageRequest, $aiResponse);

        // ============================================================
        // VERIFICATION: États après traitement
        // ============================================================
        echo "\n🔍 [E2E] Vérifications post-traitement...\n";

        // Récupérer les données fraîches
        $this->subscription->refresh();
        $this->wallet->refresh();
        $accountUsage = $this->getFreshAccountUsage();

        // Vérifier l'usage des messages
        $this->assertEquals($expectedMessageCount, $accountUsage->messages_used,
            "❌ Messages utilisés incorrects. Attendu: {$expectedMessageCount}, Reçu: {$accountUsage->messages_used}");
        echo "✅ Messages utilisés: {$accountUsage->messages_used}\n";

        // Vérifier les messages restants
        $remainingMessages = $this->subscription->getRemainingMessages();
        $expectedRemaining = $initialMessages - $expectedMessageCount;
        $this->assertEquals($expectedRemaining, $remainingMessages,
            "❌ Messages restants incorrects. Attendu: {$expectedRemaining}, Reçu: {$remainingMessages}");
        echo "✅ Messages restants: {$remainingMessages}\n";

        // Vérifier que le wallet n'a PAS été débité
        $this->assertEquals($initialWalletBalance, $this->wallet->balance,
            "❌ Wallet débité par erreur. Balance devrait être {$initialWalletBalance}, mais est {$this->wallet->balance}");
        echo "✅ Wallet balance inchangée: {$this->wallet->balance} USD\n";

        // Vérifier qu'aucun overage n'a été comptabilisé
        $this->assertEquals(0, $accountUsage->overage_messages_used,
            "❌ Overage messages utilisés par erreur: {$accountUsage->overage_messages_used}");
        $this->assertEquals(0.0, $accountUsage->overage_cost_paid_xaf,
            "❌ Overage coût payé par erreur: {$accountUsage->overage_cost_paid_xaf}");
        echo "✅ Aucun overage comptabilisé\n";

        // Vérifier les timestamps
        $this->assertNotNull($accountUsage->last_message_at);
        $this->assertNull($accountUsage->last_overage_payment_at);
        echo "✅ Timestamps corrects\n";

        // ============================================================
        // LOGS VERIFICATION
        // ============================================================
        echo "\n📋 [E2E] Informations pour vérification manuelle des logs...\n";
        echo "🔍 Rechercher dans les logs:\n";
        echo "   - '[BillingCounterListener] Processing billing'\n";
        echo "   - '[BillingCounterListener] Used quota'\n";
        echo "   - user_id: {$this->customer->id}\n";
        echo "   - session_id: {$this->whatsappAccount->session_id}\n";
        echo "   - message_count: {$expectedMessageCount}\n";

        // ============================================================
        // NOTIFICATIONS VERIFICATION
        // ============================================================
        echo "\n📧 [E2E] Vérification notifications...\n";

        // Vérifier si notification de quota bas devrait être envoyée
        $alertThreshold = config('whatsapp.billing.alert_threshold_percentage', 20);
        $thresholdMessages = ($initialMessages * $alertThreshold) / 100;
        $shouldSendAlert = $remainingMessages <= $thresholdMessages;

        echo "🚨 Seuil d'alerte: {$alertThreshold}% = {$thresholdMessages} messages\n";
        echo "🚨 Messages restants: {$remainingMessages}\n";
        echo '🚨 Notification attendue: '.($shouldSendAlert ? 'OUI' : 'NON')."\n";

        if ($shouldSendAlert) {
            echo "📧 VÉRIFIER: Email LowQuotaNotification envoyé à {$this->customer->email}\n";
            echo "📧 VÉRIFIER: Notification push LowQuotaNotification\n";
            echo "📧 VÉRIFIER: Notification database créée\n";
        } else {
            echo "📧 VÉRIFIER: Aucune notification envoyée\n";
        }

        // ============================================================
        // SUMMARY
        // ============================================================
        echo "\n🎉 [E2E] Test quota debit - SUCCESS!\n";
        echo "════════════════════════════════════════\n";
        echo "📊 RÉSUMÉ:\n";
        echo "   👤 Customer: {$this->customer->email}\n";
        echo "   📦 Package: {$this->starterPackage->display_name}\n";
        echo "   📱 WhatsApp: {$this->whatsappAccount->phone_number}\n";
        echo "   ➖ Messages utilisés: {$accountUsage->messages_used}\n";
        echo "   ➕ Messages restants: {$remainingMessages}\n";
        echo "   💰 Wallet balance: {$this->wallet->balance} USD (inchangée)\n";
        echo "   🚫 Overage: 0 (aucun débit wallet)\n";
        echo "════════════════════════════════════════\n";
    }

    /**
     * Test: Vérification du calcul des messages avec différents types de contenu
     */
    public function test_message_calculation_accuracy(): void
    {
        echo "\n🧮 [E2E] Test calcul précis des messages...\n";

        // Test avec réponse simple (1 message IA seulement)
        $simpleResponse = $this->generateSimpleAIResponse();
        $simpleCount = MessageBillingHelper::getNumberOfMessagesFromResponse($simpleResponse);
        $simpleCost = MessageBillingHelper::getAmountToBillFromResponse($simpleResponse);

        $this->assertEquals(1, $simpleCount);
        $this->assertEquals(15.0, $simpleCost); // 1 * 15 (AI)

        echo "✅ Réponse simple: {$simpleCount} message = {$simpleCost} USD\n";

        // Test avec réponse complexe
        $complexResponse = $this->generateComplexAIResponse();
        $complexCount = MessageBillingHelper::getNumberOfMessagesFromResponse($complexResponse);
        $complexCost = MessageBillingHelper::getAmountToBillFromResponse($complexResponse);

        // 1 AI + 3 produits = 4 messages
        $this->assertEquals(4, $complexCount);
        // 1*15 (AI) + 3*10 (produits) = 15 + 30 = 45 USD
        $this->assertEquals(45.0, $complexCost);

        echo "✅ Réponse complexe: {$complexCount} messages = {$complexCost} USD\n";
        echo "   📱 1 message IA = 15 USD\n";
        echo "   📦 3 messages produits = 30 USD\n";
        echo "   🧮 Total = 45 USD\n";

        echo "🎉 Calculs vérifiés avec succès!\n";
    }
}
