<?php

declare(strict_types=1);

namespace Tests\Unit\Notifications\WhatsApp;

use App\Channels\WhatsAppChannel;
use App\DTOs\WhatsApp\WhatsAppMessageRequestDTO;
use App\Models\User;
use App\Models\WhatsAppAccount;
use App\Models\WhatsAppAccountSetting;
use App\Notifications\WhatsApp\AIUnknownInformationNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Messages\MailMessage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class AIUnknownInformationNotificationTest extends TestCase
{
    use RefreshDatabase;

    private WhatsAppAccount $account;
    private WhatsAppAccountSetting $settings;
    private WhatsAppMessageRequestDTO $incomingMessage;
    private string $aiMessage;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->create();
        $this->account = WhatsAppAccount::factory()->create([
            'user_id' => $user->id,
            'agent_name' => 'Test Business',
        ]);

        $this->settings = WhatsAppAccountSetting::create([
            'whatsapp_account_id' => $this->account->id,
            'enable_email_notifications' => true,
            'notification_email' => 'admin@example.com',
            'enable_whatsapp_notifications' => true,
            'notification_whatsapp_number' => '+1234567890',
        ]);

        $this->account->refresh(); // Refresh to load the settings relationship

        $this->incomingMessage = new WhatsAppMessageRequestDTO(
            id: 'msg_123',
            from: '+33612345678',
            body: 'What are your opening hours?',
            timestamp: time(),
            type: 'text',
            isGroup: false
        );

        $this->aiMessage = 'I need to check this information for you and will get back to you shortly.';
    }

    private function createNotification(): AIUnknownInformationNotification
    {
        return new AIUnknownInformationNotification(
            $this->account,
            $this->incomingMessage,
            $this->aiMessage
        );
    }

    #[Test]
    public function it_includes_database_channel_by_default(): void
    {
        $notification = $this->createNotification();
        $channels = $notification->via($this->account);

        $this->assertContains('database', $channels);
        $this->assertContains('broadcast', $channels);
    }

    #[Test]
    public function it_includes_mail_channel_when_email_notifications_enabled(): void
    {
        $notification = $this->createNotification();
        $channels = $notification->via($this->account);

        $this->assertContains('mail', $channels);
    }

    #[Test]
    public function it_excludes_mail_channel_when_email_notifications_disabled(): void
    {
        $this->settings->update(['enable_email_notifications' => false]);
        $this->account->refresh();

        $notification = $this->createNotification();
        $channels = $notification->via($this->account);

        $this->assertNotContains('mail', $channels);
    }

    #[Test]
    public function it_includes_whatsapp_channel_when_whatsapp_notifications_enabled(): void
    {
        $notification = $this->createNotification();
        $channels = $notification->via($this->account);

        $this->assertContains(WhatsAppChannel::class, $channels);
    }

    #[Test]
    public function it_excludes_whatsapp_channel_when_whatsapp_notifications_disabled(): void
    {
        $this->settings->update(['enable_whatsapp_notifications' => false]);
        $this->account->refresh();

        $notification = $this->createNotification();
        $channels = $notification->via($this->account);

        $this->assertNotContains(WhatsAppChannel::class, $channels);
    }

    #[Test]
    public function it_generates_correct_mail_message(): void
    {
        $notification = $this->createNotification();
        $mailMessage = $notification->toMail($this->account);

        $this->assertInstanceOf(MailMessage::class, $mailMessage);
        $this->assertStringContainsString('Test Business', $mailMessage->subject);
        $this->assertStringContainsString('+33612345678', $mailMessage->introLines[1]);
        $this->assertStringContainsString('What are your opening hours?', $mailMessage->introLines[2]);
        $this->assertStringContainsString($this->aiMessage, $mailMessage->introLines[3]);
    }

    #[Test]
    public function it_generates_correct_whatsapp_message(): void
    {
        $notification = $this->createNotification();
        $whatsappMessage = $notification->toWhatsApp($this->account);

        $this->assertEquals('+1234567890', $whatsappMessage['to']);
        $this->assertStringContainsString('AI Information Request', $whatsappMessage['message']);
        $this->assertStringContainsString('+33612345678', $whatsappMessage['message']);
        $this->assertStringContainsString('What are your opening hours?', $whatsappMessage['message']);
    }

    #[Test]
    public function it_generates_correct_database_array(): void
    {
        $notification = $this->createNotification();
        $data = $notification->toArray($this->account);

        $this->assertEquals('ai_unknown_information', $data['type']);
        $this->assertEquals($this->account->id, $data['account_id']);
        $this->assertEquals('Test Business', $data['account_name']);
        $this->assertEquals('+33612345678', $data['customer_phone']);
        $this->assertEquals('What are your opening hours?', $data['customer_question']);
        $this->assertEquals($this->aiMessage, $data['ai_response']);
        $this->assertEquals($this->account->session_id, $data['session_id']);
        $this->assertArrayHasKey('created_at', $data);
    }

    #[Test]
    public function it_generates_correct_broadcast_data(): void
    {
        $notification = $this->createNotification();
        $broadcastData = $notification->toBroadcast($this->account);

        $this->assertEquals('ai_unknown_information', $broadcastData['type']);
        $this->assertEquals('AI Information Request', $broadcastData['title']);
        $this->assertStringContainsString('+33612345678', $broadcastData['message']);
        $this->assertArrayHasKey('data', $broadcastData);
        $this->assertArrayHasKey('timestamp', $broadcastData);
    }

    #[Test]
    public function it_handles_account_without_settings(): void
    {
        // Create account without settings
        $user = User::factory()->create();
        $accountWithoutSettings = WhatsAppAccount::factory()->create([
            'user_id' => $user->id,
            'agent_name' => 'No Settings Business',
        ]);

        $notification = new AIUnknownInformationNotification(
            $accountWithoutSettings,
            $this->incomingMessage,
            $this->aiMessage
        );

        $channels = $notification->via($accountWithoutSettings);

        // Should only include database and broadcast channels
        $this->assertContains('database', $channels);
        $this->assertContains('broadcast', $channels);
        $this->assertNotContains('mail', $channels);
        $this->assertNotContains(WhatsAppChannel::class, $channels);
    }

    #[Test]
    public function it_handles_different_incoming_message(): void
    {
        $messageWithoutSession = new WhatsAppMessageRequestDTO(
            id: 'msg_456',
            from: '+33612345678',
            body: 'Test message',
            timestamp: time(),
            type: 'text',
            isGroup: false
        );

        $notification = new AIUnknownInformationNotification(
            $this->account,
            $messageWithoutSession,
            $this->aiMessage
        );

        $data = $notification->toArray($this->account);

        $this->assertEquals($this->account->session_id, $data['session_id']);
        $this->assertEquals('+33612345678', $data['customer_phone']);
        $this->assertEquals('Test message', $data['customer_question']);
    }

    #[Test]
    public function it_implements_should_queue_interface(): void
    {
        $notification = $this->createNotification();

        $this->assertInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class, $notification);
    }
}
