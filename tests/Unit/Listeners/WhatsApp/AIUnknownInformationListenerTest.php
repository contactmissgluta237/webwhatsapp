<?php

declare(strict_types=1);

namespace Tests\Unit\Listeners\WhatsApp;

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

final class AIUnknownInformationListenerTest extends TestCase
{
    use RefreshDatabase;

    private WhatsAppAccount $account;
    private WhatsAppAccountSetting $settings;
    private AIUnknownInformationListener $listener;

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

        $this->account->refresh();
        $this->listener = new AIUnknownInformationListener;
    }

    private function createMessageProcessedEvent(bool $unknownInformation = true, bool $successful = true, mixed $customAccount = null): MessageProcessedEvent
    {
        $account = $customAccount ?? $this->account;

        $incomingMessage = new WhatsAppMessageRequestDTO(
            id: 'msg_123',
            from: '+33612345678',
            body: 'What are your opening hours?',
            timestamp: time(),
            type: 'text',
            isGroup: false
        );

        $aiResponse = new WhatsAppAIResponseDTO(
            response: '{"message":"I need to check this information for you","action":"text","products":[],"unknown_information":'.($unknownInformation ? 'true' : 'false').'}',
            model: 'test-model'
        );

        $responseDto = $successful
            ? WhatsAppMessageResponseDTO::success(
                aiResponse: 'Test AI response',
                aiDetails: $aiResponse,
                sessionId: $account->session_id,
                phoneNumber: $account->phone_number
            )
            : WhatsAppMessageResponseDTO::error('Processing failed');

        return new MessageProcessedEvent(
            account: $account,
            incomingMessage: $incomingMessage,
            aiResponse: $responseDto
        );
    }

    #[Test]
    public function it_does_nothing_when_event_was_not_successful(): void
    {
        Notification::fake();
        Log::shouldReceive('info')->never();

        $event = $this->createMessageProcessedEvent(true, false);
        $this->listener->handle($event);

        Notification::assertNothingSent();
    }

    #[Test]
    public function it_does_nothing_when_unknown_information_is_false(): void
    {
        Notification::fake();
        Log::shouldReceive('info')->never();

        $event = $this->createMessageProcessedEvent(false, true);
        $this->listener->handle($event);

        Notification::assertNothingSent();
    }

    #[Test]
    public function it_does_nothing_when_no_settings_configured(): void
    {
        // Delete the settings
        $this->settings->delete();
        $this->account->refresh();

        Notification::fake();
        Log::shouldReceive('info')
            ->once()
            ->with('[AI_UNKNOWN] No notifications configured for account', [
                'account_id' => $this->account->id,
                'session_id' => $this->account->session_id,
            ]);

        $event = $this->createMessageProcessedEvent(true, true);
        $this->listener->handle($event);

        Notification::assertNothingSent();
    }

    #[Test]
    public function it_does_nothing_when_notifications_are_disabled(): void
    {
        // Disable all notifications
        $this->settings->update([
            'enable_email_notifications' => false,
            'enable_whatsapp_notifications' => false,
        ]);
        $this->account->refresh();

        Notification::fake();
        Log::shouldReceive('info')
            ->once()
            ->with('[AI_UNKNOWN] No notifications configured for account', [
                'account_id' => $this->account->id,
                'session_id' => $this->account->session_id,
            ]);

        $event = $this->createMessageProcessedEvent(true, true);
        $this->listener->handle($event);

        Notification::assertNothingSent();
    }

    #[Test]
    public function it_sends_notification_when_all_conditions_are_met(): void
    {
        Notification::fake();

        Log::shouldReceive('info')
            ->once()
            ->with('[AI_UNKNOWN] Notifications processed successfully', \Mockery::type('array'));

        $event = $this->createMessageProcessedEvent(true, true);
        $this->listener->handle($event);

        Notification::assertSentTo(
            [$this->account],
            AIUnknownInformationNotification::class,
            function ($notification, $channels) {
                return $notification instanceof AIUnknownInformationNotification;
            }
        );
    }

    #[Test]
    public function it_gets_correct_event_identifiers(): void
    {
        $event = $this->createMessageProcessedEvent(true, true);

        $reflection = new \ReflectionClass($this->listener);
        $method = $reflection->getMethod('getEventIdentifiers');
        $method->setAccessible(true);

        $identifiers = $method->invoke($this->listener, $event);

        $this->assertEquals([
            'account_id' => $this->account->id,
            'session_id' => $this->account->session_id,
            'from_phone' => '+33612345678',
        ], $identifiers);
    }

    #[Test]
    public function it_handles_event_with_only_email_notifications_enabled(): void
    {
        $this->settings->update([
            'enable_whatsapp_notifications' => false,
            'notification_whatsapp_number' => null,
        ]);
        $this->account->refresh();

        Notification::fake();
        Log::shouldReceive('info')
            ->once()
            ->with('[AI_UNKNOWN] Notifications processed successfully', \Mockery::type('array'));

        $event = $this->createMessageProcessedEvent(true, true);
        $this->listener->handle($event);

        Notification::assertSentTo(
            [$this->account],
            AIUnknownInformationNotification::class
        );
    }

    #[Test]
    public function it_handles_event_with_only_whatsapp_notifications_enabled(): void
    {
        $this->settings->update([
            'enable_email_notifications' => false,
            'notification_email' => null,
        ]);
        $this->account->refresh();

        Notification::fake();
        Log::shouldReceive('info')
            ->once()
            ->with('[AI_UNKNOWN] Notifications processed successfully', \Mockery::type('array'));

        $event = $this->createMessageProcessedEvent(true, true);
        $this->listener->handle($event);

        Notification::assertSentTo(
            [$this->account],
            AIUnknownInformationNotification::class
        );
    }
}
