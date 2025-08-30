<?php

declare(strict_types=1);

namespace Tests\Feature\WhatsApp;

use App\DTOs\WhatsApp\WhatsAppAIResponseDTO;
use App\DTOs\WhatsApp\WhatsAppMessageRequestDTO;
use App\DTOs\WhatsApp\WhatsAppMessageResponseDTO;
use App\Events\WhatsApp\MessageProcessedEvent;
use App\Models\User;
use App\Models\WhatsAppAccount;
use App\Models\WhatsAppAccountSetting;
use App\Notifications\WhatsApp\AIUnknownInformationNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class NotificationIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private WhatsAppAccount $account;
    private WhatsAppAccountSetting $settings;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->account = WhatsAppAccount::factory()->create([
            'user_id' => $this->user->id,
            'agent_name' => 'Integration Test Business',
        ]);

        $this->settings = WhatsAppAccountSetting::create([
            'whatsapp_account_id' => $this->account->id,
            'enable_email_notifications' => true,
            'notification_email' => 'admin@testbusiness.com',
            'enable_whatsapp_notifications' => true,
            'notification_whatsapp_number' => '+33612345678',
        ]);

        $this->account->refresh();
    }

    private function createMessageProcessedEvent(bool $unknownInformation = true): MessageProcessedEvent
    {
        $incomingMessage = new WhatsAppMessageRequestDTO(
            id: 'integration_msg_123',
            from: '+33987654321',
            body: 'What are your store hours on Sundays?',
            timestamp: time(),
            type: 'text',
            isGroup: false
        );

        $aiResponse = new WhatsAppAIResponseDTO(
            response: json_encode([
                'message' => 'I need to check this information and will get back to you.',
                'action' => 'text',
                'products' => [],
                'unknown_information' => $unknownInformation,
            ]),
            model: 'gpt-4'
        );

        $responseDto = WhatsAppMessageResponseDTO::success(
            aiResponse: 'I need to check this information and will get back to you.',
            aiDetails: $aiResponse,
            sessionId: $this->account->session_id,
            phoneNumber: $this->account->phone_number
        );

        return new MessageProcessedEvent(
            account: $this->account,
            incomingMessage: $incomingMessage,
            aiResponse: $responseDto
        );
    }

    #[Test]
    public function it_sends_notifications_when_ai_has_unknown_information(): void
    {
        Notification::fake();

        // Dispatch the event (this would normally be done by the message processing system)
        $event = $this->createMessageProcessedEvent(true);
        Event::dispatch($event);

        // Verify notification was sent
        Notification::assertSentTo(
            $this->account,
            AIUnknownInformationNotification::class,
            function ($notification) {
                return $notification instanceof AIUnknownInformationNotification;
            }
        );
    }

    #[Test]
    public function it_does_not_send_notifications_when_ai_knows_the_information(): void
    {
        Notification::fake();

        // Dispatch event with unknown_information = false
        $event = $this->createMessageProcessedEvent(false);
        Event::dispatch($event);

        // Verify no notifications were sent
        Notification::assertNothingSent();
    }

    #[Test]
    public function it_sends_email_notification_when_enabled(): void
    {
        Notification::fake();

        // Enable only email notifications
        $this->settings->update([
            'enable_whatsapp_notifications' => false,
            'notification_whatsapp_number' => null,
        ]);
        $this->account->refresh();

        $event = $this->createMessageProcessedEvent(true);
        Event::dispatch($event);

        Notification::assertSentTo(
            $this->account,
            AIUnknownInformationNotification::class,
            function ($notification) {
                $channels = $notification->via($this->account);

                return in_array('mail', $channels) && ! in_array(\App\Channels\WhatsAppChannel::class, $channels);
            }
        );
    }

    #[Test]
    public function it_sends_whatsapp_notification_when_enabled(): void
    {
        Notification::fake();

        // Enable only WhatsApp notifications
        $this->settings->update([
            'enable_email_notifications' => false,
            'notification_email' => null,
        ]);
        $this->account->refresh();

        $event = $this->createMessageProcessedEvent(true);
        Event::dispatch($event);

        Notification::assertSentTo(
            $this->account,
            AIUnknownInformationNotification::class,
            function ($notification) {
                $channels = $notification->via($this->account);

                return in_array(\App\Channels\WhatsAppChannel::class, $channels) && ! in_array('mail', $channels);
            }
        );
    }

    #[Test]
    public function it_sends_both_notification_types_when_both_enabled(): void
    {
        Notification::fake();

        $event = $this->createMessageProcessedEvent(true);
        Event::dispatch($event);

        Notification::assertSentTo(
            $this->account,
            AIUnknownInformationNotification::class,
            function ($notification) {
                $channels = $notification->via($this->account);

                return in_array('mail', $channels) && in_array(\App\Channels\WhatsAppChannel::class, $channels);
            }
        );
    }

    #[Test]
    public function it_does_not_send_notifications_when_no_settings_exist(): void
    {
        Notification::fake();

        // Remove settings
        $this->settings->delete();
        $this->account->refresh();

        $event = $this->createMessageProcessedEvent(true);
        Event::dispatch($event);

        Notification::assertNothingSent();
    }

    #[Test]
    public function it_does_not_send_notifications_when_all_channels_disabled(): void
    {
        Notification::fake();

        // Disable all notifications
        $this->settings->update([
            'enable_email_notifications' => false,
            'notification_email' => null,
            'enable_whatsapp_notifications' => false,
            'notification_whatsapp_number' => null,
        ]);
        $this->account->refresh();

        $event = $this->createMessageProcessedEvent(true);
        Event::dispatch($event);

        Notification::assertNothingSent();
    }

    #[Test]
    public function it_stores_notification_in_database(): void
    {
        Notification::fake();

        $event = $this->createMessageProcessedEvent(true);
        Event::dispatch($event);

        Notification::assertSentTo(
            $this->account,
            AIUnknownInformationNotification::class,
            function ($notification) {
                $channels = $notification->via($this->account);

                return in_array('database', $channels);
            }
        );
    }

    #[Test]
    public function it_broadcasts_notification(): void
    {
        Notification::fake();

        $event = $this->createMessageProcessedEvent(true);
        Event::dispatch($event);

        Notification::assertSentTo(
            $this->account,
            AIUnknownInformationNotification::class,
            function ($notification) {
                $channels = $notification->via($this->account);

                return in_array('broadcast', $channels);
            }
        );
    }

    #[Test]
    public function it_processes_notification_with_correct_message_data(): void
    {
        Notification::fake();

        $event = $this->createMessageProcessedEvent(true);
        Event::dispatch($event);

        Notification::assertSentTo(
            $this->account,
            AIUnknownInformationNotification::class,
            function (AIUnknownInformationNotification $notification) {
                $data = $notification->toArray($this->account);

                return $data['type'] === 'ai_unknown_information'
                    && $data['customer_phone'] === '+33987654321'
                    && $data['customer_question'] === 'What are your store hours on Sundays?'
                    && $data['account_name'] === 'Integration Test Business'
                    && $data['session_id'] === $this->account->session_id;
            }
        );
    }

    #[Test]
    public function notification_configuration_route_is_accessible(): void
    {
        $this->actingAs($this->user);

        $response = $this->get(route('whatsapp.notifications.config', $this->account->id));

        $response->assertStatus(200);
        $response->assertSeeLivewire(\App\Livewire\Customer\WhatsApp\NotificationConfig::class);
    }

    #[Test]
    public function notification_configuration_route_requires_authentication(): void
    {
        $response = $this->get(route('whatsapp.notifications.config', $this->account->id));

        $response->assertRedirect(route('login'));
    }

    #[Test]
    public function notification_configuration_route_requires_account_ownership(): void
    {
        $otherUser = User::factory()->create();
        $this->actingAs($otherUser);

        $response = $this->get(route('whatsapp.notifications.config', $this->account->id));

        $response->assertStatus(403);
    }
}
