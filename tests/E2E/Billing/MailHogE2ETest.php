<?php

declare(strict_types=1);

namespace Tests\E2E\Billing;

/**
 * Test E2E pour voir les mails réellement dans MailHog (localhost:8025)
 *
 * Ce test envoie des mails immédiatement (sans queue) pour que tu puisses
 * les voir dans l'interface MailHog
 */
class MailHogE2ETest extends BaseE2EBillingTest
{
    /**
     * Test pour envoyer un vrai mail visible dans MailHog
     *
     * 🌍 Après ce test, va sur http://localhost:8025 pour voir les emails !
     */
    public function test_send_real_emails_to_mailhog(): void
    {
        // Clear processed events to avoid anti-multilistening protection
        \App\Listeners\BaseListener::clearProcessedEvents();

        echo "\n📧 [MailHog] Test envoi emails réels...\n";
        echo "🌍 Ouvre http://localhost:8025 après ce test pour voir les emails!\n\n";

        // ============================================================
        // EMAIL 1: Alerte Quota Bas
        // ============================================================
        echo "📍 EMAIL 1: Alerte quota critique...\n";

        $accountUsage = $this->getFreshAccountUsage();
        $accountUsage->update(['messages_used' => 85]); // 15 restants = 15%

        $messageRequest = $this->generateMessageRequest('Test quota critique pour MailHog');
        $aiResponse = $this->generateSimpleAIResponse();

        $remainingBefore = $this->subscription->fresh()->getRemainingMessages();
        echo "📊 Messages restants avant: {$remainingBefore}\n";

        // Dispatch l'événement
        $this->dispatchMessageProcessedEvent($messageRequest, $aiResponse);

        $remainingAfter = $this->subscription->fresh()->getRemainingMessages();
        echo "📊 Messages restants après: {$remainingAfter}\n";
        echo "📧 ✅ Email LowQuotaMail envoyé vers MailHog!\n";

        // ============================================================
        // EMAIL 2: Débit Wallet
        // ============================================================
        echo "\n📍 EMAIL 2: Débit wallet...\n";

        // Épuiser le quota complètement
        $accountUsage->update(['messages_used' => 100]);

        $messageRequest2 = $this->generateMessageRequest('Test débit wallet pour MailHog');
        $aiResponse2 = $this->generateSimpleAIResponse(); // 15 XAF

        $initialBalance = $this->wallet->balance;
        echo "📊 Balance wallet avant: {$initialBalance} XAF\n";

        // Dispatch l'événement
        $this->dispatchMessageProcessedEvent($messageRequest2, $aiResponse2);

        $this->wallet->refresh();
        $finalBalance = $this->wallet->balance;
        $debitedAmount = $initialBalance - $finalBalance;

        echo "📊 Balance wallet après: {$finalBalance} XAF\n";
        echo "💳 Montant débité: {$debitedAmount} XAF\n";
        echo "📧 ✅ Email WalletDebitedMail envoyé vers MailHog!\n";

        // ============================================================
        // INSTRUCTIONS POUR L'UTILISATEUR
        // ============================================================
        echo "\n🌍 [MailHog] Instructions pour voir les emails:\n";
        echo "════════════════════════════════════════════════════════════════\n";
        echo "1. 🌐 Ouvre ton navigateur\n";
        echo "2. 🔗 Va sur: http://localhost:8025\n";
        echo "3. 📧 Tu devrais voir 2 nouveaux emails:\n";
        echo "   📮 Email 1: 'Quota WhatsApp bientôt épuisé' \n";
        echo "   📮 Email 2: 'Votre wallet a été débité'\n";
        echo "4. 👁️  Clique sur chaque email pour voir le contenu\n";
        echo "════════════════════════════════════════════════════════════════\n";

        echo "\n📊 [MailHog] Détails des emails envoyés:\n";
        echo "📧 Destinataire: {$this->customer->email}\n";
        echo "📧 Email 1: LowQuotaMail ({$remainingAfter} messages restants)\n";
        echo "📧 Email 2: WalletDebitedMail ({$debitedAmount} XAF débités)\n";

        echo "\n🎉 [MailHog] Test terminé - Va vérifier tes emails! 📬\n";

        // Assertions pour s'assurer que la logique fonctionne
        $this->assertLessThan($remainingBefore, $remainingAfter, 'Les messages devraient avoir diminué');
        $this->assertLessThan($initialBalance, $finalBalance, 'Le wallet devrait avoir été débité');
    }

    /**
     * Test pour spam quelques emails pour bien voir dans MailHog
     */
    public function test_send_multiple_emails_to_mailhog(): void
    {
        // Clear processed events to avoid anti-multilistening protection
        \App\Listeners\BaseListener::clearProcessedEvents();

        echo "\n📧 [MailHog] Test envoi multiple emails...\n";

        for ($i = 1; $i <= 3; $i++) {
            echo "\n🔄 Envoi email #{$i}...\n";

            // Reset l'état pour chaque test
            $accountUsage = $this->getFreshAccountUsage();
            $accountUsage->update(['messages_used' => 85 + $i]); // 15, 14, 13 restants

            $messageRequest = $this->generateMessageRequest("Test #{$i} pour MailHog");
            $aiResponse = $this->generateSimpleAIResponse();

            $this->dispatchMessageProcessedEvent($messageRequest, $aiResponse);

            echo "📧 ✅ Email #{$i} envoyé!\n";

            // Petite pause pour que les emails soient distincts
            usleep(100000); // 0.1 secondes
        }

        echo "\n🌍 Tu devrais maintenant avoir plusieurs emails dans http://localhost:8025 ! 📬\n";
    }
}
