<?php

declare(strict_types=1);

namespace Tests\Feature\WhatsApp;

use App\DTOs\WhatsApp\WhatsAppAIResponseDTO;
use App\DTOs\WhatsApp\WhatsAppMessageRequestDTO;
use App\DTOs\WhatsApp\WhatsAppMessageResponseDTO;
use App\Events\WhatsApp\MessageProcessedEvent;
use App\Listeners\WhatsApp\AIUnknownInformationListener;
use App\Models\User;
use App\Models\WhatsAppAccount;
use App\Models\WhatsAppAccountSetting;
use App\Notifications\WhatsApp\AIUnknownInformationNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class AIUnknownInformationE2ETest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_processes_complete_flow_and_formats_phone_number_correctly(): void
    {
        // Arrange - Créer les données de test
        $user = User::factory()->create();

        $account = WhatsAppAccount::factory()->create([
            'user_id' => $user->id,
            'session_id' => 'test_e2e_session',
            'phone_number' => '237123456789',
        ]);

        // Settings avec numéro de notification AVEC + (comme en prod)
        WhatsAppAccountSetting::create([
            'whatsapp_account_id' => $account->id,
            'enable_whatsapp_notifications' => true,
            'notification_whatsapp_number' => '+23755332183', // AVEC + !
            'enable_email_notifications' => true,
            'notification_email' => 'test@example.com',
        ]);

        $account = $account->fresh(['settings']);

        // Message entrant DTO (sans + comme dans les vrais logs)
        $incomingMessage = new WhatsAppMessageRequestDTO(
            id: 'test_e2e_message_id',
            from: '23755332183@c.us', // SANS +
            body: 'Où êtes-vous situés exactement ?',
            timestamp: time(),
            type: 'text',
            isGroup: false
        );

        // Réponse AI avec unknown_information = true
        $aiResponseJson = '{"message": "Je dois vérifier cette information", "action": "text", "products": [], "unknown_information": true}';

        $aiResponseDto = new WhatsAppAIResponseDTO(
            response: $aiResponseJson,
            model: 'test-model',
            confidence: 0.8
        );

        $messageResponseDto = WhatsAppMessageResponseDTO::success(
            aiResponse: 'Je dois vérifier cette information',
            aiDetails: $aiResponseDto,
            sessionId: $account->session_id,
            phoneNumber: $incomingMessage->from
        );

        // Fake les notifications pour capturer les données
        Notification::fake();

        // Act - Créer et dispatcher l'événement
        $event = new MessageProcessedEvent(
            $account,
            $incomingMessage,
            $messageResponseDto
        );

        // Instancier et appeler le listener directement
        $listener = new AIUnknownInformationListener;
        $listener->handle($event);

        // Assert - Vérifier que la notification a été envoyée
        Notification::assertSentTo(
            $account,
            AIUnknownInformationNotification::class,
            function (AIUnknownInformationNotification $notification) use ($account) {
                // Récupérer les données WhatsApp de la notification
                $whatsappData = $notification->toWhatsApp($account);

                // VÉRIFICATION CRITIQUE : Le numéro doit être SANS +
                $this->assertEquals('23755332183', $whatsappData['to'],
                    'Le numéro de téléphone doit être sans le + pour WhatsApp');

                // Vérifications supplémentaires
                $this->assertEquals($account->session_id, $whatsappData['session_id']);
                $this->assertStringContainsString('Demande d\'Information IA', $whatsappData['message']);
                $this->assertStringContainsString('23755332183', $whatsappData['message']); // Le numéro du client

                // Log pour debug
                Log::info('[E2E_TEST] WhatsApp notification data', [
                    'input_notification_number' => $account->settings->notification_whatsapp_number,
                    'output_to_field' => $whatsappData['to'],
                    'format_correct' => ! str_starts_with($whatsappData['to'], '+'),
                ]);

                return true;
            }
        );

        // Vérification finale explicite
        $testNotification = new AIUnknownInformationNotification(
            $account,
            $incomingMessage,
            'Test AI response'
        );

        $finalData = $testNotification->toWhatsApp($account);

        $this->assertEquals('23755332183', $finalData['to'],
            'ÉCHEC CRITIQUE : Le numéro contient encore un +');

        $this->assertStringNotContainsString('+', $finalData['to'],
            'ÉCHEC CRITIQUE : Le champ "to" ne doit pas contenir de +');

        echo "\n✅ E2E TEST PASSED:\n";
        echo "   📞 Input: {$account->settings->notification_whatsapp_number}\n";
        echo "   📤 Output: {$finalData['to']}\n";
        echo '   🎯 Format correct: '.(! str_starts_with($finalData['to'], '+') ? 'OUI' : 'NON')."\n";
    }
}
