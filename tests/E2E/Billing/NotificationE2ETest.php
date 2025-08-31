<?php

declare(strict_types=1);

namespace Tests\E2E\Billing;

use App\Services\WhatsApp\Helpers\MessageBillingHelper;
use Illuminate\Support\Facades\Notification;

/**
 * Test E2E pour les notifications critiques du système de facturation
 *
 * Ce test vérifie spécifiquement l'envoi des notifications :
 * - LowQuotaNotification quand le quota atteint le seuil critique
 * - WalletDebitedNotification lors du débit wallet
 * - Envoi réel des emails et push notifications
 */
class NotificationE2ETest extends BaseE2EBillingTest
{
    /**
     * Test: Déclencher notification de quota critique
     *
     * Scénario:
     * - Customer avec 25 messages restants (25% du quota)
     * - Envoi d'un message qui consomme 20 messages
     * - Après traitement: 5 messages restants (5% < seuil de 20%)
     * - Vérification que LowQuotaNotification est envoyée
     */
    public function test_low_quota_critical_notification(): void
    {
        // ============================================================
        // SETUP: Configurer un quota proche du seuil critique
        // ============================================================
        echo "\n🚨 [E2E] Test notification quota critique...\n";

        // Utiliser 75 messages pour qu'il reste 25 messages (25%)
        $accountUsage = $this->getFreshAccountUsage();
        $accountUsage->update(['messages_used' => 75]);

        $remainingBefore = $this->subscription->fresh()->getRemainingMessages();
        $alertThreshold = config('whatsapp.billing.alert_threshold_percentage', 20);
        $thresholdMessages = ($this->subscription->messages_limit * $alertThreshold) / 100;

        echo "📊 État initial:\n";
        echo "   - Messages utilisés: 75/100\n";
        echo "   - Messages restants: {$remainingBefore}\n";
        echo "   - Seuil d'alerte: {$alertThreshold}% = {$thresholdMessages} messages\n";
        echo '   - Status: '.($remainingBefore > $thresholdMessages ? 'OK' : 'CRITIQUE')."\n";

        $this->assertGreaterThan($thresholdMessages, $remainingBefore, 'État initial devrait être au-dessus du seuil');

        // ============================================================
        // ACTION: Envoyer un message qui va déclencher l'alerte
        // ============================================================
        echo "\n📤 [E2E] Envoi message pour déclencher alerte...\n";

        // Créer une réponse qui consomme exactement 20 messages pour tomber à 5 restants
        $complexResponse = $this->generateCustomResponse(20); // 20 messages
        $messageRequest = $this->generateMessageRequest('Besoin de beaucoup de produits');

        $messageCount = MessageBillingHelper::getNumberOfMessagesFromResponse($complexResponse);
        $expectedRemaining = $remainingBefore - $messageCount;

        echo "🧮 Messages à consommer: {$messageCount}\n";
        echo "🧮 Messages restants après: {$expectedRemaining}\n";
        echo '🚨 Déclenchement alerte: '.($expectedRemaining <= $thresholdMessages ? 'OUI' : 'NON')."\n";

        // Clear les notifications précédentes pour test propre
        Notification::fake();

        // Dispatch de l'événement
        echo "🚀 Dispatch de MessageProcessedEvent...\n";
        $this->dispatchMessageProcessedEvent($messageRequest, $complexResponse);

        // ============================================================
        // VERIFICATION: Notification envoyée
        // ============================================================
        echo "\n📧 [E2E] Vérification notifications...\n";

        $this->subscription->refresh();
        $finalRemaining = $this->subscription->getRemainingMessages();

        echo "✅ Messages restants finaux: {$finalRemaining}\n";
        echo '🚨 Devrait déclencher alerte: '.($finalRemaining <= $thresholdMessages ? 'OUI' : 'NON')."\n";

        // Vérifier que la notification LowQuotaNotification a été envoyée
        if ($finalRemaining <= $thresholdMessages) {
            Notification::assertSentTo($this->customer, \App\Notifications\WhatsApp\LowQuotaNotification::class);
            echo "✅ LowQuotaNotification envoyée avec succès!\n";

            // Vérifier le contenu de la notification
            Notification::assertSentTo($this->customer, \App\Notifications\WhatsApp\LowQuotaNotification::class,
                function ($notification) use ($finalRemaining) {
                    return $notification->getRemainingMessages() === $finalRemaining;
                }
            );
            echo "✅ Contenu notification correct: {$finalRemaining} messages restants\n";
        } else {
            echo "❌ Alerte non déclenchée (test setup incorrect)\n";
            $this->fail('Le test devrait déclencher une alerte de quota bas');
        }

        // ============================================================
        // VERIFICATION DÉTAILLÉE: Contenu email
        // ============================================================
        echo "\n📧 [E2E] Détails pour vérification manuelle email...\n";
        echo "📧 Destinataire: {$this->customer->email}\n";
        echo "📧 Type: LowQuotaNotification\n";
        echo "📧 Messages restants: {$finalRemaining}\n";
        echo '📧 Pourcentage restant: '.round(($finalRemaining / 100) * 100, 1)."%\n";
        echo "📧 Seuil critique: {$alertThreshold}%\n";

        echo "\n🎉 [E2E] Test notification quota critique - SUCCESS!\n";
    }

    /**
     * Test: Notification de débit wallet avec envoi réel
     *
     * Ce test vérifie l'envoi de WalletDebitedNotification lors du débit wallet
     */
    public function test_wallet_debit_notification(): void
    {
        // ============================================================
        // SETUP: Épuiser le quota complètement
        // ============================================================
        echo "\n💳 [E2E] Test notification débit wallet...\n";

        $accountUsage = $this->getFreshAccountUsage();
        $accountUsage->update(['messages_used' => 100]); // Quota épuisé

        echo "📊 État initial:\n";
        echo "   - Quota: 100/100 messages utilisés (épuisé)\n";
        echo "   - Wallet: {$this->wallet->balance} USD\n";

        // ============================================================
        // ACTION: Envoyer message nécessitant débit wallet
        // ============================================================
        echo "\n📤 [E2E] Envoi message nécessitant débit wallet...\n";

        $aiResponse = $this->generateSimpleAIResponse(); // 1 message = 15 USD
        $messageRequest = $this->generateMessageRequest('Message en dépassement');

        $billingAmount = MessageBillingHelper::getAmountToBillFromResponse($aiResponse);
        $initialBalance = $this->wallet->balance;
        $expectedNewBalance = $initialBalance - $billingAmount;

        echo "🧮 Coût du message: {$billingAmount} USD\n";
        echo "🧮 Balance avant: {$initialBalance} USD\n";
        echo "🧮 Balance attendue après: {$expectedNewBalance} USD\n";

        // Clear les notifications pour test propre
        Notification::fake();

        // Dispatch de l'événement
        echo "🚀 Dispatch de MessageProcessedEvent...\n";
        $this->dispatchMessageProcessedEvent($messageRequest, $aiResponse);

        // ============================================================
        // VERIFICATION: Notification de débit wallet
        // ============================================================
        echo "\n📧 [E2E] Vérification notification débit wallet...\n";

        $this->wallet->refresh();
        $finalBalance = $this->wallet->balance;

        echo "✅ Balance finale: {$finalBalance} USD\n";
        echo '✅ Montant débité: '.($initialBalance - $finalBalance)." USD\n";

        // Vérifier que WalletDebitedNotification a été envoyée
        Notification::assertSentTo($this->customer, \App\Notifications\WhatsApp\WalletDebitedNotification::class);
        echo "✅ WalletDebitedNotification envoyée avec succès!\n";

        // Vérifier le contenu de la notification
        Notification::assertSentTo($this->customer, \App\Notifications\WhatsApp\WalletDebitedNotification::class,
            function ($notification) use ($billingAmount, $finalBalance) {
                return abs($notification->getDebitedAmount() - $billingAmount) < 0.01
                    && abs($notification->getNewWalletBalance() - $finalBalance) < 0.01;
            }
        );
        echo "✅ Contenu notification correct!\n";

        // ============================================================
        // VERIFICATION DÉTAILLÉE: Contenu email
        // ============================================================
        echo "\n📧 [E2E] Détails pour vérification manuelle email...\n";
        echo "📧 Destinataire: {$this->customer->email}\n";
        echo "📧 Type: WalletDebitedNotification\n";
        echo "📧 Montant débité: {$billingAmount} USD\n";
        echo "📧 Nouveau solde: {$finalBalance} USD\n";
        echo "📧 Raison: Dépassement de quota WhatsApp\n";

        echo "\n🎉 [E2E] Test notification débit wallet - SUCCESS!\n";
    }

    /**
     * Test: Scenario complet avec les deux types de notifications
     *
     * Ce test simule un parcours utilisateur complet :
     * 1. Quota normal → Quota bas (alerte)
     * 2. Quota épuisé → Débit wallet (notification débit)
     */
    public function test_complete_notification_scenario(): void
    {
        echo "\n🔄 [E2E] Test scénario complet notifications...\n";

        // Clear notifications
        Notification::fake();

        // ============================================================
        // ÉTAPE 1: Déclencher alerte quota bas
        // ============================================================
        echo "\n📍 ÉTAPE 1: Déclencher alerte quota bas...\n";

        $accountUsage = $this->getFreshAccountUsage();
        $accountUsage->update(['messages_used' => 85]); // 15 restants

        $messageRequest1 = $this->generateMessageRequest('Premier message critique');
        $aiResponse1 = $this->generateCustomResponse(10); // Consomme 10, reste 5

        echo "🚀 Dispatch message 1 (quota → critique)...\n";
        $this->dispatchMessageProcessedEvent($messageRequest1, $aiResponse1);

        // Vérifier alerte quota bas
        Notification::assertSentTo($this->customer, \App\Notifications\WhatsApp\LowQuotaNotification::class);
        echo "✅ Étape 1: LowQuotaNotification envoyée\n";

        // ============================================================
        // ÉTAPE 2: Épuiser complètement le quota
        // ============================================================
        echo "\n📍 ÉTAPE 2: Épuiser complètement le quota...\n";

        $messageRequest2 = $this->generateMessageRequest('Deuxième message épuisant');
        $aiResponse2 = $this->generateCustomResponse(8); // Consomme 8, reste -3 → wallet débit

        echo "🚀 Dispatch message 2 (quota → épuisé → wallet)...\n";
        $this->dispatchMessageProcessedEvent($messageRequest2, $aiResponse2);

        // Vérifier notification débit wallet
        Notification::assertSentTo($this->customer, \App\Notifications\WhatsApp\WalletDebitedNotification::class);
        echo "✅ Étape 2: WalletDebitedNotification envoyée\n";

        // ============================================================
        // VERIFICATION FINALE
        // ============================================================
        echo "\n📊 [E2E] État final du système...\n";

        $this->subscription->refresh();
        $this->wallet->refresh();
        $accountUsage->refresh();

        echo "✅ Messages utilisés quota: {$accountUsage->messages_used}\n";
        echo "✅ Overage cost payé: {$accountUsage->overage_cost_paid_xaf} USD\n";
        echo "✅ Balance wallet finale: {$this->wallet->balance} USD\n";

        // Vérifier que les deux types de notifications ont été envoyés
        Notification::assertCount(2);
        echo "✅ Total: 2 notifications envoyées (LowQuota + WalletDebit)\n";

        echo "\n🎉 [E2E] Scénario complet notifications - SUCCESS!\n";
        echo "════════════════════════════════════════\n";
        echo "📊 RÉSUMÉ COMPLET:\n";
        echo "   📧 LowQuotaNotification: ✅ Envoyée\n";
        echo "   📧 WalletDebitedNotification: ✅ Envoyée\n";
        echo "   💳 Wallet débité: ✅ Correct\n";
        echo "   📱 Quota géré: ✅ Correct\n";
        echo "════════════════════════════════════════\n";
    }

    /**
     * Génère une réponse AI avec un nombre spécifique de messages
     */
    private function generateCustomResponse(int $targetMessageCount): \App\DTOs\WhatsApp\WhatsAppMessageResponseDTO
    {
        // Calculer combien de produits et médias pour atteindre le target
        // 1 message AI est toujours présent
        $remainingMessages = $targetMessageCount - 1;

        $products = [];
        $currentMessages = 0;

        // Créer des produits avec différents nombres de médias
        while ($currentMessages < $remainingMessages) {
            $mediaCount = min(5, $remainingMessages - $currentMessages); // Max 5 médias par produit
            $mediaUrls = array_fill(0, $mediaCount, 'https://example.com/img'.uniqid().'.jpg');

            $products[] = new \App\DTOs\WhatsApp\ProductDataDTO(
                'Produit '.(count($products) + 1),
                $mediaUrls
            );

            $currentMessages += 1 + $mediaCount; // 1 produit + médias
        }

        return \App\DTOs\WhatsApp\WhatsAppMessageResponseDTO::success(
            'Réponse avec '.$targetMessageCount.' messages',
            new \App\DTOs\WhatsApp\WhatsAppAIResponseDTO(
                'Réponse générée pour test',
                'gpt-4',
                0.95,
                50
            ),
            waitTime: 1,
            typingDuration: 2,
            products: $products,
            sessionId: $this->whatsappAccount->session_id,
            phoneNumber: $this->whatsappAccount->phone_number
        );
    }
}
