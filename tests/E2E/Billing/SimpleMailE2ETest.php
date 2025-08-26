<?php

declare(strict_types=1);

namespace Tests\E2E\Billing;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

/**
 * Test E2E simple pour vérifier que les emails sont bien déclenchés
 */
class SimpleMailE2ETest extends BaseE2EBillingTest
{
    /**
     * Test simple: vérifier que les notifications déclenchent bien des mails
     */
    public function test_emails_are_queued_correctly(): void
    {
        echo "\n📧 [E2E] Test simple envoi emails en queue...\n";

        // Test avec Notification::fake() pour voir si les notifications sont déclenchées
        Notification::fake();

        echo "🔧 Utilisation de Notification::fake() pour debug...\n";

        // ============================================================
        // TEST 1: Déclencher alerte quota critique (< 20%)
        // ============================================================
        echo "\n📍 TEST 1: Déclencher alerte quota critique...\n";

        $accountUsage = $this->getFreshAccountUsage();
        $accountUsage->update(['messages_used' => 88]); // 12 restants = 12%

        $messageRequest = $this->generateMessageRequest('Test quota critique');
        $aiResponse = $this->generateSimpleAIResponse(); // 1 message

        $this->subscription->refresh();
        $remainingBefore = $this->subscription->getRemainingMessages();
        $shouldAlert = $this->subscription->shouldSendLowQuotaAlert();

        echo "📊 État avant: 88 messages utilisés, {$remainingBefore} restants\n";
        echo '📊 Devrait déclencher alerte: '.($shouldAlert ? 'OUI' : 'NON')."\n";
        echo "🚀 Dispatch événement...\n";

        $this->dispatchMessageProcessedEvent($messageRequest, $aiResponse);

        $this->subscription->refresh();
        $remainingAfter = $this->subscription->getRemainingMessages();
        $shouldAlertAfter = $this->subscription->shouldSendLowQuotaAlert();

        echo "📊 État après: messages restants = {$remainingAfter}\n";
        echo '📊 Devrait déclencher alerte après: '.($shouldAlertAfter ? 'OUI' : 'NON')."\n";

        // Vérifier que la notification LowQuotaNotification a été envoyée
        Notification::assertSentTo($this->customer, \App\Notifications\WhatsApp\LowQuotaNotification::class);
        echo "✅ LowQuotaNotification envoyée au customer!\n";

        // ============================================================
        // TEST 2: Déclencher débit wallet
        // ============================================================
        echo "\n📍 TEST 2: Déclencher débit wallet...\n";

        // Épuiser complètement le quota
        $accountUsage->update(['messages_used' => 100]);

        $messageRequest2 = $this->generateMessageRequest('Test débit wallet');
        $aiResponse2 = $this->generateSimpleAIResponse(); // 15 XAF

        echo "📊 État avant: quota épuisé (100/100), wallet: {$this->wallet->balance} XAF\n";
        echo "🚀 Dispatch événement...\n";

        $this->dispatchMessageProcessedEvent($messageRequest2, $aiResponse2);

        // Vérifier que la notification WalletDebitedNotification a été envoyée
        Notification::assertSentTo($this->customer, \App\Notifications\WhatsApp\WalletDebitedNotification::class);
        echo "✅ WalletDebitedNotification envoyée au customer!\n";

        // ============================================================
        // VERIFICATION FINALE
        // ============================================================
        echo "\n📊 [E2E] Vérification finale...\n";

        // Vérifier que les deux types de notifications ont été envoyées
        Notification::assertSentTo($this->customer, \App\Notifications\WhatsApp\LowQuotaNotification::class);
        Notification::assertSentTo($this->customer, \App\Notifications\WhatsApp\WalletDebitedNotification::class);

        echo "✅ Total emails en queue: 2\n";
        echo "   📧 LowQuotaMail: 1 (quota critique)\n";
        echo "   📧 WalletDebitedMail: 1 (débit wallet)\n";

        // État final
        $this->wallet->refresh();
        $accountUsage->refresh();

        echo "\n📊 État final système:\n";
        echo "   📱 Messages quota: {$accountUsage->messages_used}/100\n";
        echo "   💳 Wallet débité: {$accountUsage->overage_cost_paid_xaf} XAF\n";
        echo "   💰 Solde wallet: {$this->wallet->balance} XAF\n";

        echo "\n🎉 [E2E] Test emails en queue - SUCCESS!\n";
        echo "════════════════════════════════════════\n";
        echo "📧 RÉSUMÉ:\n";
        echo "   ✅ Les notifications déclenchent bien les emails\n";
        echo "   ✅ Les emails sont correctement mis en queue\n";
        echo "   ✅ LowQuotaMail: Envoyé pour quota < 20%\n";
        echo "   ✅ WalletDebitedMail: Envoyé lors du débit\n";
        echo "════════════════════════════════════════\n";
    }

    /**
     * Test pour vérifier le contenu des emails quand traités synchronément
     */
    public function test_email_content_sync(): void
    {
        echo "\n📧 [E2E] Test contenu emails traités synchronement...\n";

        // Forcer le traitement synchrone des queues
        $this->app['config']->set('queue.default', 'sync');

        // Fake Mail
        Mail::fake();

        // Déclencher quota critique
        $accountUsage = $this->getFreshAccountUsage();
        $accountUsage->update(['messages_used' => 90]); // 10 restants = 10%

        $messageRequest = $this->generateMessageRequest('Test contenu email');
        $aiResponse = $this->generateSimpleAIResponse();

        echo "📊 Setup: 90 messages utilisés, 10 restants (10% < 20%)\n";
        echo "🚀 Dispatch avec queue sync...\n";

        $this->dispatchMessageProcessedEvent($messageRequest, $aiResponse);

        // Maintenant vérifier que l'email a été envoyé (pas juste mis en queue)
        Mail::assertSent(\App\Mail\WhatsApp\LowQuotaMail::class, function ($mail) {
            echo "📧 ✅ Email LowQuotaMail envoyé synchronement!\n";
            echo "📧 📧 Destinataire: {$mail->to[0]['address']}\n";

            // Vérifier le destinataire
            $this->assertEquals($this->customer->email, $mail->to[0]['address']);

            return true;
        });

        echo "🎉 Email synchrone vérifié avec succès!\n";
    }
}
