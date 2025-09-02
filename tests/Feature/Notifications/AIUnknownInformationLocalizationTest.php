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

    #[Test]
    public function it_uses_user_locale_for_whatsapp_notification(): void
    {
        // Arrange - Create test data with explicit French locale
        $user = User::factory()->create([
            'first_name' => 'Jean',
            'last_name' => 'Français',
            'email' => 'jean.francais@locale-test.com',
            'locale' => 'fr', // Explicit French locale
        ]);

        $account = WhatsAppAccount::factory()->create([
            'user_id' => $user->id,
            'agent_name' => 'Test Business FR',
            'session_name' => 'test-session-fr',
            'session_id' => 'test_session_fr_123',
        ]);

        $settings = WhatsAppAccountSetting::create([
            'whatsapp_account_id' => $account->id,
            'enable_email_notifications' => true,
            'notification_email' => 'test.fr@example.com',
            'enable_whatsapp_notifications' => true,
            'notification_whatsapp_number' => '+33612345678',
        ]);

        // Set application to English to test that notification uses user's French locale
        App::setLocale('en');

        $messageRequest = new WhatsAppMessageRequestDTO(
            id: 'french_test_message_id',
            from: '33612345679@c.us',
            body: 'Quelle est votre politique de retour?',
            timestamp: now()->timestamp,
            type: 'text',
            isGroup: false
        );

        $notification = new AIUnknownInformationNotification(
            $account,
            $messageRequest,
            'Je ne peux pas répondre à cette question pour le moment.',
            456
        );

        // Act
        $whatsappData = $notification->toWhatsApp($account);

        // Assert - Verify French text appears in WhatsApp notification
        $this->assertStringContainsString('Demande d\'Information IA', $whatsappData['message']);
        $this->assertStringContainsString('Votre assistant IA a besoin d\'aide !', $whatsappData['message']);
        $this->assertStringContainsString('Client', $whatsappData['message']);
        $this->assertStringContainsString('Question', $whatsappData['message']);
        $this->assertStringContainsString('Réponse IA', $whatsappData['message']);
        $this->assertStringContainsString('Je ne peux pas répondre à cette question pour le moment.', $whatsappData['message']);

        // Verify phone number and session data
        $this->assertEquals('33612345678', $whatsappData['to']);
        $this->assertEquals('test_session_fr_123', $whatsappData['session_id']);
    }

    #[Test]
    public function it_uses_english_when_user_locale_is_english(): void
    {
        // Arrange - Create test data with explicit English locale
        $user = User::factory()->create([
            'first_name' => 'John',
            'last_name' => 'English',
            'email' => 'john.english@locale-test.com',
            'locale' => 'en', // Explicit English locale
        ]);

        $account = WhatsAppAccount::factory()->create([
            'user_id' => $user->id,
            'agent_name' => 'Test Business EN',
            'session_name' => 'test-session-en',
            'session_id' => 'test_session_en_789',
        ]);

        WhatsAppAccountSetting::create([
            'whatsapp_account_id' => $account->id,
            'enable_email_notifications' => true,
            'notification_email' => 'test.en@example.com',
            'enable_whatsapp_notifications' => true,
            'notification_whatsapp_number' => '+44987654321',
        ]);

        // Set application to French to test that notification uses user's English locale
        App::setLocale('fr');

        $messageRequest = new WhatsAppMessageRequestDTO(
            id: 'english_test_message_id',
            from: '44987654322@c.us',
            body: 'What is your return policy?',
            timestamp: now()->timestamp,
            type: 'text',
            isGroup: false
        );

        $notification = new AIUnknownInformationNotification(
            $account,
            $messageRequest,
            'I cannot answer this question at the moment.',
            789
        );

        // Act
        $whatsappData = $notification->toWhatsApp($account);

        // Assert - Verify English text appears in WhatsApp notification
        $this->assertStringContainsString('AI Information Request', $whatsappData['message']);
        $this->assertStringContainsString('Your AI assistant needs help', $whatsappData['message']);
        $this->assertStringContainsString('Customer', $whatsappData['message']);
        $this->assertStringContainsString('Question', $whatsappData['message']);
        $this->assertStringContainsString('AI Response', $whatsappData['message']);
        $this->assertStringContainsString('I cannot answer this question at the moment.', $whatsappData['message']);

        // Verify phone number and session data
        $this->assertEquals('44987654321', $whatsappData['to']);
        $this->assertEquals('test_session_en_789', $whatsappData['session_id']);
    }

    #[Test]
    public function it_includes_ai_response_in_whatsapp_message(): void
    {
        // Arrange - Create test data with explicit values
        $user = User::factory()->create([
            'first_name' => 'Maria',
            'last_name' => 'ResponseTest',
            'email' => 'maria.response@response-test.com',
            'locale' => 'fr',
        ]);

        $account = WhatsAppAccount::factory()->create([
            'user_id' => $user->id,
            'agent_name' => 'Response Test Business',
            'session_name' => 'test-response-session',
            'session_id' => 'test_response_session_101',
        ]);

        WhatsAppAccountSetting::create([
            'whatsapp_account_id' => $account->id,
            'enable_email_notifications' => true,
            'notification_email' => 'maria.response@test.com',
            'enable_whatsapp_notifications' => true,
            'notification_whatsapp_number' => '+34612345678',
        ]);

        $messageRequest = new WhatsAppMessageRequestDTO(
            id: 'response_inclusion_test_message',
            from: '34612345679@c.us',
            body: 'Quelle est votre politique de retour?',
            timestamp: now()->timestamp,
            type: 'text',
            isGroup: false
        );

        $customAiResponse = 'Je ne peux pas répondre à cette question pour le moment. Veuillez contacter notre service client au +34123456789.';

        $notification = new AIUnknownInformationNotification(
            $account,
            $messageRequest,
            $customAiResponse,
            101
        );

        // Act
        $whatsappData = $notification->toWhatsApp($account);

        // Assert - Verify the custom AI response is included in the message
        $this->assertStringContainsString($customAiResponse, $whatsappData['message']);
        $this->assertStringContainsString('Quelle est votre politique de retour?', $whatsappData['message']);
        // The phone number is displayed without @c.us suffix in the notification
        $this->assertStringContainsString('34612345679', $whatsappData['message']);
    }

    #[Test]
    public function it_preserves_app_locale_after_notification(): void
    {
        // Arrange - Create test data with different locale than app
        $user = User::factory()->create([
            'first_name' => 'Pedro',
            'last_name' => 'LocalePreservation',
            'email' => 'pedro.locale@preservation-test.com',
            'locale' => 'fr', // User uses French
        ]);

        $account = WhatsAppAccount::factory()->create([
            'user_id' => $user->id,
            'agent_name' => 'Locale Test Business',
            'session_name' => 'locale-preservation-session',
            'session_id' => 'locale_preserve_session_202',
        ]);

        WhatsAppAccountSetting::create([
            'whatsapp_account_id' => $account->id,
            'enable_email_notifications' => false,
            'enable_whatsapp_notifications' => true,
            'notification_whatsapp_number' => '+351987654321',
        ]);

        // Set application to English and capture original locale
        App::setLocale('en');
        $originalLocale = App::getLocale();

        $messageRequest = new WhatsAppMessageRequestDTO(
            id: 'locale_preservation_test_message',
            from: '351987654322@c.us',
            body: 'Test question for locale preservation',
            timestamp: now()->timestamp,
            type: 'text',
            isGroup: false
        );

        $notification = new AIUnknownInformationNotification(
            $account,
            $messageRequest,
            'Test AI response for locale preservation',
            202
        );

        // Act - Execute notification methods
        $notification->toWhatsApp($account);
        $notification->toArray($account);

        // Assert - Verify that the application locale hasn't changed
        $this->assertEquals($originalLocale, App::getLocale(), 'Application locale should be preserved after notification');
        $this->assertEquals('en', App::getLocale(), 'Application should still be in English');
    }

    #[Test]
    public function it_uses_default_locale_when_user_has_default_fr(): void
    {
        // Arrange - Create user with default locale (fr) - testing the fallback behavior
        $user = User::factory()->create([
            'first_name' => 'Carlos',
            'last_name' => 'DefaultLocale',
            'email' => 'carlos.default@fallback-test.com',
            // No explicit locale set, so will use database default 'fr'
        ]);

        $account = WhatsAppAccount::factory()->create([
            'user_id' => $user->id,
            'agent_name' => 'Default Locale Test Business',
            'session_name' => 'default-locale-session',
            'session_id' => 'default_locale_session_303',
        ]);

        WhatsAppAccountSetting::create([
            'whatsapp_account_id' => $account->id,
            'enable_whatsapp_notifications' => true,
            'notification_whatsapp_number' => '+52987654321',
        ]);

        // Set app to English to ensure it uses user's default locale
        App::setLocale('en');

        $messageRequest = new WhatsAppMessageRequestDTO(
            id: 'default_locale_test_message',
            from: '52987654322@c.us',
            body: 'Test default locale question',
            timestamp: now()->timestamp,
            type: 'text',
            isGroup: false
        );

        $notification = new AIUnknownInformationNotification(
            $account,
            $messageRequest,
            'Test default locale AI response',
            303
        );

        // Act
        $whatsappData = $notification->toWhatsApp($account);
        $arrayData = $notification->toArray($account);

        // Assert - Should use French (the database default locale)
        $this->assertStringContainsString('Demande d\'Information IA', $whatsappData['message']);
        $this->assertEquals('Demande d\'Information IA', $arrayData['title']);
        $this->assertEquals('fr', $user->locale, 'User should have default French locale');
    }
}
