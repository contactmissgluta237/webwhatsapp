<?php

declare(strict_types=1);

namespace Tests\E2E\Billing;

use App\Services\WhatsApp\Helpers\MessageBillingHelper;
use Illuminate\Support\Facades\Mail;

/**
 * Test E2E pour vérifier l'envoi réel des emails de notifications
 *
 * Ce test utilise Mail::fake() pour capturer les emails envoyés
 * et vérifier leur contenu détaillé
 */
class MailSendingE2ETest extends BaseE2EBillingTest
{
    protected function setUp(): void
    {
        parent::setUp();

        // Forcer les queues à être traitées synchronément pour tous les tests
        config(['queue.default' => 'sync']);
    }

    /**
     * Test: Vérification détaillée de l'email LowQuotaNotification
     */
    public function test_low_quota_email_content(): void
    {
        echo "\n📧 [E2E] Test contenu email quota bas...\n";

        // Setup: Quota proche du seuil
        $accountUsage = $this->getFreshAccountUsage();
        $accountUsage->update(['messages_used' => 85]); // 15 restants

        // Forcer les queues à être traitées synchronément pour les tests
        config(['queue.default' => 'sync']);

        // Fake Mail pour capturer les emails
        Mail::fake();

        // Action: Déclencher alerte quota bas
        $messageRequest = $this->generateMessageRequest('Message test quota bas');
        $aiResponse = $this->generateCustomResponse(12); // Consomme 12, reste 3

        echo "🚀 Dispatch pour déclencher alerte quota bas...\n";
        $this->dispatchMessageProcessedEvent($messageRequest, $aiResponse);

        // Vérification: Email LowQuotaMail envoyé
        Mail::assertSent(\App\Mail\WhatsApp\LowQuotaMail::class, function ($mail) {
            echo "📧 ✅ Email LowQuotaMail capturé!\n";
            echo "📧 📧 Destinataire: {$mail->to[0]['address']}\n";

            // Vérifier que l'email contient les bonnes données
            $this->assertEquals($this->customer->email, $mail->to[0]['address']);

            return true;
        });

        echo "🎉 Email quota bas vérifié avec succès!\n";
    }

    /**
     * Test: Vérification détaillée de l'email WalletDebitedNotification
     */
    public function test_wallet_debited_email_content(): void
    {
        echo "\n💳 [E2E] Test contenu email débit wallet...\n";

        // Setup: Quota épuisé
        $accountUsage = $this->getFreshAccountUsage();
        $accountUsage->update(['messages_used' => 100]); // Quota épuisé

        $initialBalance = $this->wallet->balance;

        // Forcer les queues à être traitées synchronément pour les tests
        config(['queue.default' => 'sync']);

        // Fake Mail pour capturer les emails
        Mail::fake();

        // Action: Déclencher débit wallet
        $messageRequest = $this->generateMessageRequest('Message nécessitant débit wallet');
        $aiResponse = $this->generateSimpleAIResponse(); // 15 XAF

        $expectedCost = MessageBillingHelper::getAmountToBillFromResponse($aiResponse);
        $expectedNewBalance = $initialBalance - $expectedCost;

        echo "🧮 Coût attendu: {$expectedCost} XAF\n";
        echo "🧮 Balance attendue: {$expectedNewBalance} XAF\n";

        echo "🚀 Dispatch pour déclencher débit wallet...\n";
        $this->dispatchMessageProcessedEvent($messageRequest, $aiResponse);

        // Vérification: Email WalletDebitedMail envoyé
        Mail::assertSent(\App\Mail\WhatsApp\WalletDebitedMail::class, function ($mail) {
            echo "📧 ✅ Email WalletDebitedMail capturé!\n";
            echo "📧 📧 Destinataire: {$mail->to[0]['address']}\n";
            echo "📧 📧 Sujet: {$mail->subject}\n";

            // Vérifier que l'email contient les bonnes données
            $this->assertEquals($this->customer->email, $mail->to[0]['address']);
            $this->assertStringContainsString('débit', strtolower($mail->subject));

            return true;
        });

        echo "🎉 Email débit wallet vérifié avec succès!\n";
    }

    /**
     * Test: Scénario complet avec capture de tous les emails
     */
    public function test_complete_email_scenario(): void
    {
        echo "\n📬 [E2E] Test scénario complet envoi emails...\n";

        // Forcer les queues à être traitées synchronément pour les tests
        config(['queue.default' => 'sync']);

        // Fake Mail pour capturer TOUS les emails
        Mail::fake();

        // ============================================================
        // ÉTAPE 1: Déclencher alerte quota bas
        // ============================================================
        echo "\n📍 ÉTAPE 1: Déclencher email quota bas...\n";

        $accountUsage = $this->getFreshAccountUsage();
        $accountUsage->update(['messages_used' => 82]); // 18 restants

        $messageRequest1 = $this->generateMessageRequest('Premier message');
        $aiResponse1 = $this->generateCustomResponse(15); // Consomme 15, reste 3 < 20%

        $this->dispatchMessageProcessedEvent($messageRequest1, $aiResponse1);

        // Vérifier que l'email quota bas a été envoyé
        Mail::assertSent(\App\Mail\WhatsApp\LowQuotaMail::class);
        echo "✅ ÉTAPE 1: Email LowQuotaMail envoyé\n";

        // ============================================================
        // ÉTAPE 2: Épuiser quota et déclencher débit wallet
        // ============================================================
        echo "\n📍 ÉTAPE 2: Déclencher email débit wallet...\n";

        $messageRequest2 = $this->generateMessageRequest('Deuxième message');
        $aiResponse2 = $this->generateCustomResponse(5); // Épuise quota + débit wallet

        $this->dispatchMessageProcessedEvent($messageRequest2, $aiResponse2);

        // Vérifier que l'email débit wallet a été envoyé
        Mail::assertSent(\App\Mail\WhatsApp\WalletDebitedMail::class);
        echo "✅ ÉTAPE 2: Email WalletDebitedMail envoyé\n";

        // ============================================================
        // VERIFICATION FINALE
        // ============================================================
        echo "\n📊 [E2E] Vérification finale emails...\n";

        // Vérifier le nombre total d'emails envoyés
        Mail::assertSent(\App\Mail\WhatsApp\LowQuotaMail::class, 1);
        Mail::assertSent(\App\Mail\WhatsApp\WalletDebitedMail::class, 1);

        echo "✅ Total emails envoyés: 2\n";
        echo "   📧 LowQuotaMail: 1\n";
        echo "   📧 WalletDebitedMail: 1\n";

        // État final du système
        $this->subscription->refresh();
        $this->wallet->refresh();
        $accountUsage->refresh();

        echo "\n📊 État final système:\n";
        echo "   📱 Messages quota utilisés: {$accountUsage->messages_used}/100\n";
        echo "   💳 Overage payé: {$accountUsage->overage_cost_paid_xaf} XAF\n";
        echo "   💰 Balance wallet: {$this->wallet->balance} XAF\n";

        echo "\n🎉 [E2E] Scénario complet emails - SUCCESS!\n";
        echo "════════════════════════════════════════\n";
        echo "📧 RÉSUMÉ EMAILS:\n";
        echo "   ✅ LowQuotaMail: Envoyé quand quota < 20%\n";
        echo "   ✅ WalletDebitedMail: Envoyé lors débit wallet\n";
        echo "   ✅ Destinataire: {$this->customer->email}\n";
        echo "   ✅ Tous les emails capturés et vérifiés\n";
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
                'Réponse générée pour test email',
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
