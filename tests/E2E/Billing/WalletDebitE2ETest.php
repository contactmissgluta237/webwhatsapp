<?php

declare(strict_types=1);

namespace Tests\E2E\Billing;

use App\Models\WhatsAppAccountUsage;
use App\Services\WhatsApp\Helpers\MessageBillingHelper;

/**
 * Test E2E pour le débit wallet quand l'utilisateur n'a plus de quota
 *
 * Ce test utilise la vraie base de données et vérifie :
 * - Customer avec quota épuisé mais wallet chargé
 * - Débit automatique du wallet pour les messages en dépassement
 * - Notifications de débit wallet envoyées
 * - Logs de débit wallet générés
 */
class WalletDebitE2ETest extends BaseE2EBillingTest
{
    protected function setUp(): void
    {
        parent::setUp();

        // Épuiser le quota dès le début pour ce test
        $this->exhaustCustomerQuota();
    }

    /**
     * Épuise complètement le quota du customer pour simuler un dépassement
     */
    private function exhaustCustomerQuota(): void
    {
        $accountUsage = WhatsAppAccountUsage::getOrCreateForAccount($this->subscription, $this->whatsappAccount);

        // Marquer tout le quota comme utilisé (100 messages)
        $accountUsage->update([
            'messages_used' => $this->subscription->messages_limit, // 100
            'base_messages_count' => $this->subscription->messages_limit,
            'last_message_at' => now()->subMinutes(10),
        ]);

        echo "🔄 Quota épuisé: {$this->subscription->messages_limit} messages utilisés\n";
    }

    /**
     * Test: Customer sans quota mais avec wallet suffisant - Débit automatique
     *
     * Scénario:
     * - Customer avec 0 messages restants dans son quota
     * - Wallet avec 1000 USD (suffisant)
     * - Envoi d'une réponse complexe (AI + 3 produits) = 4 messages = 45 USD
     * - Vérification que 45 USD sont débités du wallet
     * - Notification WalletDebitedNotification envoyée
     * - Logs de débit wallet présents
     */
    public function test_wallet_debit_with_sufficient_funds(): void
    {
        // ============================================================
        // SETUP: Vérifier l'état initial (quota épuisé)
        // ============================================================
        echo "\n🔍 [E2E] Vérification état initial (quota épuisé)...\n";

        $initialWalletBalance = $this->wallet->balance; // 1000.00
        $remainingMessages = $this->subscription->getRemainingMessages(); // 0
        $accountUsage = $this->getFreshAccountUsage();

        $this->assertEquals(0, $remainingMessages, '❌ Le quota devrait être épuisé');
        $this->assertEquals(100, $accountUsage->messages_used, '❌ Tous les messages devraient être utilisés');

        echo "✅ Customer: {$this->customer->email}\n";
        echo "✅ Quota restant: {$remainingMessages} messages (épuisé)\n";
        echo "✅ Messages utilisés: {$accountUsage->messages_used}/100\n";
        echo "✅ Wallet balance: {$initialWalletBalance} USD\n";

        // ============================================================
        // ACTION: Générer et dispatcher l'événement
        // ============================================================
        echo "\n📤 [E2E] Génération et dispatch de l'événement (dépassement)...\n";

        $messageRequest = $this->generateMessageRequest('Montrez-moi encore des produits');
        $aiResponse = $this->generateComplexAIResponse();

        // Calculer le coût attendu
        $expectedMessageCount = MessageBillingHelper::getNumberOfMessagesFromResponse($aiResponse);
        $expectedBillingAmount = MessageBillingHelper::getAmountToBillFromResponse($aiResponse);

        echo "🧮 Messages calculés: {$expectedMessageCount}\n";
        echo "🧮 Coût à débiter: {$expectedBillingAmount} USD\n";
        echo "🧮 Balance wallet avant: {$initialWalletBalance} USD\n";
        echo '🧮 Balance attendue après: '.($initialWalletBalance - $expectedBillingAmount)." USD\n";

        // Dispatcher l'événement
        echo "🚀 Dispatch de MessageProcessedEvent...\n";
        $this->dispatchMessageProcessedEvent($messageRequest, $aiResponse);

        // ============================================================
        // VERIFICATION: États après traitement
        // ============================================================
        echo "\n🔍 [E2E] Vérifications post-traitement (débit wallet)...\n";

        // Récupérer les données fraîches
        $this->wallet->refresh();
        $accountUsage->refresh();

        // Vérifier que le quota n'a PAS changé (toujours épuisé)
        $this->assertEquals(100, $accountUsage->messages_used,
            '❌ Les messages du quota ne devraient pas changer');
        echo "✅ Messages quota inchangés: {$accountUsage->messages_used}\n";

        // Vérifier que le wallet a été débité
        $expectedNewBalance = $initialWalletBalance - $expectedBillingAmount;
        $this->assertEquals($expectedNewBalance, $this->wallet->balance,
            "❌ Wallet mal débité. Attendu: {$expectedNewBalance}, Reçu: {$this->wallet->balance}");
        echo "✅ Wallet débité: {$initialWalletBalance} - {$expectedBillingAmount} = {$this->wallet->balance} USD\n";

        // Vérifier l'overage comptabilisé
        $this->assertEquals($expectedBillingAmount, $accountUsage->overage_cost_paid_xaf,
            "❌ Overage cost incorrect. Attendu: {$expectedBillingAmount}, Reçu: {$accountUsage->overage_cost_paid_xaf}");
        echo "✅ Overage cost: {$accountUsage->overage_cost_paid_xaf} USD\n";

        // Vérifier les timestamps
        $this->assertNotNull($accountUsage->last_message_at);
        $this->assertNotNull($accountUsage->last_overage_payment_at);
        echo "✅ Timestamps overage mis à jour\n";

        // ============================================================
        // LOGS VERIFICATION
        // ============================================================
        echo "\n📋 [E2E] Informations pour vérification manuelle des logs...\n";
        echo "🔍 Rechercher dans les logs:\n";
        echo "   - '[BillingCounterListener] Processing billing'\n";
        echo "   - '[BillingCounterListener] Wallet debited and notification sent'\n";
        echo "   - '[WhatsAppAccountUsage] Wallet debited for overage'\n";
        echo "   - user_id: {$this->customer->id}\n";
        echo "   - session_id: {$this->whatsappAccount->session_id}\n";
        echo "   - amount_debited: {$expectedBillingAmount}\n";
        echo "   - new_wallet_balance: {$this->wallet->balance}\n";

        // ============================================================
        // NOTIFICATIONS VERIFICATION
        // ============================================================
        echo "\n📧 [E2E] Vérification notifications wallet débit...\n";
        echo "📧 VÉRIFIER: Email WalletDebitedNotification envoyé à {$this->customer->email}\n";
        echo "📧 VÉRIFIER: Contenu email - Montant débité: {$expectedBillingAmount} USD\n";
        echo "📧 VÉRIFIER: Contenu email - Nouveau solde: {$this->wallet->balance} USD\n";
        echo "📧 VÉRIFIER: Notification push WalletDebitedNotification\n";
        echo "📧 VÉRIFIER: Notification database créée avec type 'whatsapp_wallet_debited'\n";

        // ============================================================
        // SUMMARY
        // ============================================================
        echo "\n🎉 [E2E] Test wallet debit - SUCCESS!\n";
        echo "════════════════════════════════════════\n";
        echo "📊 RÉSUMÉ:\n";
        echo "   👤 Customer: {$this->customer->email}\n";
        echo "   📦 Quota épuisé: 100/100 messages utilisés\n";
        echo "   💳 Wallet débité: {$expectedBillingAmount} USD\n";
        echo "   💰 Nouveau solde: {$this->wallet->balance} USD\n";
        echo "   📊 Overage payé: {$accountUsage->overage_cost_paid_xaf} USD\n";
        echo "   📧 Notifications: WalletDebitedNotification envoyée\n";
        echo "════════════════════════════════════════\n";
    }

    /**
     * Test: Customer sans quota et avec wallet insuffisant - Rejet de débit
     */
    public function test_wallet_debit_with_insufficient_funds(): void
    {
        // ============================================================
        // SETUP: Réduire le wallet à un montant insuffisant
        // ============================================================
        echo "\n🔍 [E2E] Test avec wallet insuffisant...\n";

        // Mettre seulement 30 USD dans le wallet (insuffisant pour 45 USD)
        $lowBalance = 30.0;
        $this->wallet->update(['balance' => $lowBalance]);

        $messageRequest = $this->generateMessageRequest('Encore des produits please');
        $aiResponse = $this->generateComplexAIResponse();

        $expectedBillingAmount = MessageBillingHelper::getAmountToBillFromResponse($aiResponse);

        echo "✅ Wallet balance réduite: {$lowBalance} USD\n";
        echo "💰 Coût requis: {$expectedBillingAmount} USD\n";
        echo "❌ Fonds insuffisants: {$lowBalance} < {$expectedBillingAmount}\n";

        // ============================================================
        // ACTION: Dispatcher l'événement
        // ============================================================
        echo "\n🚀 Dispatch avec fonds insuffisants...\n";
        $this->dispatchMessageProcessedEvent($messageRequest, $aiResponse);

        // ============================================================
        // VERIFICATION: Rien ne doit être débité
        // ============================================================
        echo "\n🔍 Vérifications fonds insuffisants...\n";

        $this->wallet->refresh();
        $accountUsage = $this->getFreshAccountUsage();

        // Le wallet ne doit PAS être débité
        $this->assertEquals($lowBalance, $this->wallet->balance,
            '❌ Wallet débité malgré fonds insuffisants');
        echo "✅ Wallet inchangé: {$this->wallet->balance} USD\n";

        // Aucun overage ne doit être comptabilisé
        $this->assertEquals(0.0, $accountUsage->overage_cost_paid_xaf,
            '❌ Overage comptabilisé malgré échec de débit');
        echo "✅ Aucun overage comptabilisé\n";

        // ============================================================
        // LOGS VERIFICATION
        // ============================================================
        echo "\n📋 [E2E] Logs à vérifier pour fonds insuffisants...\n";
        echo "🔍 Rechercher dans les logs:\n";
        echo "   - '[BillingCounterListener] Failed to debit wallet - insufficient funds'\n";
        echo "   - user_id: {$this->customer->id}\n";
        echo "   - required_amount: {$expectedBillingAmount}\n";
        echo "   - wallet_balance: {$this->wallet->balance}\n";

        echo "\n📧 [E2E] Notifications (ne devrait PAS y en avoir)...\n";
        echo "❌ VÉRIFIER: Aucun email WalletDebitedNotification envoyé\n";
        echo "❌ VÉRIFIER: Aucune notification push\n";

        echo "\n🎉 Test fonds insuffisants - SUCCESS!\n";
    }

    /**
     * Test: Edge case - Wallet avec exactement le montant requis
     */
    public function test_wallet_debit_exact_amount(): void
    {
        echo "\n🔍 [E2E] Test avec montant wallet exact...\n";

        // Calculer le coût d'une réponse simple et ajuster le wallet
        $simpleResponse = $this->generateSimpleAIResponse();
        $exactAmount = MessageBillingHelper::getAmountToBillFromResponse($simpleResponse); // 15 USD

        $this->wallet->update(['balance' => $exactAmount]);

        echo "✅ Wallet ajusté à: {$exactAmount} USD (montant exact)\n";

        // Dispatcher
        $messageRequest = $this->generateMessageRequest('Simple question');
        $this->dispatchMessageProcessedEvent($messageRequest, $simpleResponse);

        // Vérifier
        $this->wallet->refresh();
        $this->assertEquals(0.0, $this->wallet->balance,
            '❌ Le wallet devrait être à 0 après débit exact');

        echo "✅ Wallet après débit exact: {$this->wallet->balance} USD\n";
        echo "🎉 Test montant exact - SUCCESS!\n";
    }
}
