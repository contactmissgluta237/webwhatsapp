<?php

declare(strict_types=1);

namespace Tests\Feature\Notifications;

use App\DTOs\WhatsApp\WhatsAppMessageRequestDTO;
use App\Models\User;
use App\Models\WhatsAppAccount;
use App\Models\WhatsAppAccountSetting;
use App\Notifications\WhatsApp\AIUnknownInformationNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class AIUnknownInformationLocalizationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private WhatsAppAccount $account;
    private WhatsAppAccountSetting $settings;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'locale' => 'fr',
        ]);

        $this->account = WhatsAppAccount::factory()->create([
            'user_id' => $this->user->id,
            'agent_name' => 'Test Business',
        ]);

        $this->settings = WhatsAppAccountSetting::create([
            'whatsapp_account_id' => $this->account->id,
            'enable_email_notifications' => true,
            'notification_email' => 'test@example.com',
            'enable_whatsapp_notifications' => true,
            'notification_whatsapp_number' => '+33612345678',
        ]);
    }

    #[Test]
    public function it_uses_user_locale_for_whatsapp_notification(): void
    {
        // Définir l'application en anglais par défaut
        App::setLocale('en');

        $messageRequest = new WhatsAppMessageRequestDTO(
            id: 'test_message_id',
            from: '33612345679@c.us',
            body: 'Quelle est votre politique de retour?',
            timestamp: now()->timestamp,
            type: 'text',
            isGroup: false
        );

        $notification = new AIUnknownInformationNotification(
            $this->account,
            $messageRequest,
            'Je ne peux pas répondre à cette question pour le moment.',
            123
        );

        $whatsappData = $notification->toWhatsApp($this->account);

        // Vérifier que le message contient les textes en français
        $this->assertStringContainsString('Demande d\'Information IA', $whatsappData['message']);
        $this->assertStringContainsString('Votre assistant IA a besoin d\'aide', $whatsappData['message']);
        $this->assertStringContainsString('Client', $whatsappData['message']);
        $this->assertStringContainsString('Question', $whatsappData['message']);
        $this->assertStringContainsString('Réponse IA', $whatsappData['message']);
        $this->assertStringContainsString('Je ne peux pas répondre à cette question pour le moment.', $whatsappData['message']);
    }

    #[Test]
    public function it_uses_english_when_user_locale_is_english(): void
    {
        // Changer la locale de l'utilisateur
        $this->user->update(['locale' => 'en']);

        // Définir l'application en français par défaut
        App::setLocale('fr');

        $messageRequest = new WhatsAppMessageRequestDTO(
            id: 'test_message_id',
            from: '33612345679@c.us',
            body: 'What is your return policy?',
            timestamp: now()->timestamp,
            type: 'text',
            isGroup: false
        );

        $notification = new AIUnknownInformationNotification(
            $this->account,
            $messageRequest,
            'I cannot answer this question at the moment.',
            123
        );

        $whatsappData = $notification->toWhatsApp($this->account);

        // Vérifier que le message contient les textes en anglais
        $this->assertStringContainsString('AI Information Request', $whatsappData['message']);
        $this->assertStringContainsString('Your AI assistant needs help', $whatsappData['message']);
        $this->assertStringContainsString('Customer', $whatsappData['message']);
        $this->assertStringContainsString('Question', $whatsappData['message']);
        $this->assertStringContainsString('AI Response', $whatsappData['message']);
        $this->assertStringContainsString('I cannot answer this question at the moment.', $whatsappData['message']);
    }

    #[Test]
    public function it_includes_ai_response_in_whatsapp_message(): void
    {
        $messageRequest = new WhatsAppMessageRequestDTO(
            id: 'test_message_id',
            from: '33612345679@c.us',
            body: 'Quelle est votre politique de retour?',
            timestamp: now()->timestamp,
            type: 'text',
            isGroup: false
        );

        $aiResponse = 'Je ne peux pas répondre à cette question pour le moment. Veuillez contacter notre service client.';

        $notification = new AIUnknownInformationNotification(
            $this->account,
            $messageRequest,
            $aiResponse,
            123
        );

        $whatsappData = $notification->toWhatsApp($this->account);

        // Vérifier que la réponse IA est incluse dans le message
        $this->assertStringContainsString($aiResponse, $whatsappData['message']);
    }

    #[Test]
    public function it_preserves_app_locale_after_notification(): void
    {
        // Définir l'application en anglais
        App::setLocale('en');
        $originalLocale = App::getLocale();

        $messageRequest = new WhatsAppMessageRequestDTO(
            id: 'test_message_id',
            from: '33612345679@c.us',
            body: 'Test question',
            timestamp: now()->timestamp,
            type: 'text',
            isGroup: false
        );

        $notification = new AIUnknownInformationNotification(
            $this->account,
            $messageRequest,
            'Test AI response',
            123
        );

        // Exécuter les méthodes de notification
        $notification->toWhatsApp($this->account);
        $notification->toArray($this->account);
        $notification->toBroadcast($this->account);

        // Vérifier que la locale de l'application n'a pas changé
        $this->assertEquals($originalLocale, App::getLocale());
    }
}
