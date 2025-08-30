<?php

declare(strict_types=1);

namespace Tests\E2E;

use App\DTOs\WhatsApp\WhatsAppMessageRequestDTO;
use App\Models\WhatsAppAccount;
use App\Models\WhatsAppAccountSetting;
use App\Notifications\WhatsApp\AIUnknownInformationNotification;
use Tests\TestCase;

final class WhatsAppNotificationFormatTest extends TestCase
{
    #[\PHPUnit\Framework\Attributes\Test]
    public function it_removes_plus_from_phone_number_in_whatsapp_notification(): void
    {
        // Test direct sans base de données - création d'objets mock
        $account = new WhatsAppAccount;
        $account->id = 1;
        $account->session_id = 'test_session';

        $settings = new WhatsAppAccountSetting;
        $settings->notification_whatsapp_number = '+23755332183'; // AVEC +
        $settings->enable_whatsapp_notifications = true;
        $settings->enable_email_notifications = true;
        $settings->notification_email = 'test@example.com';

        // Simule la relation
        $account->setRelation('settings', $settings);

        // Arrange: Crée un message entrant
        $incomingMessage = new WhatsAppMessageRequestDTO(
            id: 'test_message_'.time(),
            from: '23755332183@c.us', // SANS + (format normal entrant)
            body: 'Où êtes-vous situés ?',
            timestamp: time(),
            type: 'text',
            isGroup: false
        );

        // Act: Crée la notification directement
        $notification = new AIUnknownInformationNotification(
            $account,
            $incomingMessage,
            'Test AI response'
        );

        // Assert: Vérifie le format du numéro dans toWhatsApp
        $whatsappData = $notification->toWhatsApp($account);

        // Le numéro de destination ne doit PAS avoir de +
        $this->assertArrayHasKey('to', $whatsappData);
        $this->assertEquals('23755332183', $whatsappData['to'],
            'Le numéro de destination doit être sans + : '.$whatsappData['to']);

        // Vérifie que c'est différent de l'original avec +
        $originalNumber = $account->settings->notification_whatsapp_number;
        $this->assertEquals('+23755332183', $originalNumber, 'Le numéro original doit avoir un +');
        $this->assertNotEquals($originalNumber, $whatsappData['to'], 'Le + doit être retiré');

        echo "
✅ Test réussi : '{$originalNumber}' → '{$whatsappData['to']}'
";
    }
}
