<?php

declare(strict_types=1);

namespace Tests\Unit\Channels;

use App\Channels\WhatsAppChannel;
use App\DTOs\WhatsApp\WhatsAppMessageRequestDTO;
use App\Models\User;
use App\Models\WhatsAppAccount;
use App\Models\WhatsAppAccountSetting;
use App\Notifications\WhatsApp\AIUnknownInformationNotification;
use App\Services\WhatsApp\WhatsAppNotificationHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class WhatsAppNotificationChannelTest extends TestCase
{
    use RefreshDatabase;

    private WhatsAppAccount $account;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test account
        $user = User::factory()->create();
        $this->account = WhatsAppAccount::factory()->create([
            'user_id' => $user->id,
            'session_id' => 'test_session_123',
        ]);

        WhatsAppAccountSetting::create([
            'whatsapp_account_id' => $this->account->id,
            'enable_whatsapp_notifications' => true,
            'notification_whatsapp_number' => '+237676636794',
        ]);
    }

    #[Test]
    public function it_sends_notification_successfully(): void
    {
        Http::fake([
            '*' => Http::response(['success' => true], 200),
        ]);

        $handler = app(WhatsAppNotificationHandler::class);
        $channel = new WhatsAppChannel($handler);

        $notification = $this->createMockNotification([
            'session_id' => 'test_session_123',
            'to' => '+237676636794',
            'message' => 'Test notification message',
        ]);

        $channel->send($this->account, $notification);

        Http::assertSent(function ($request) {
            return $request['session_id'] === 'test_session_123'
                && $request['to'] === '237676636794@c.us'
                && $request['message'] === 'Test notification message';
        });
    }

    #[Test]
    public function it_skips_notification_without_to_whats_app_method(): void
    {
        Http::fake();

        $handler = app(WhatsAppNotificationHandler::class);
        $channel = new WhatsAppChannel($handler);

        $notification = new class extends Notification
        {
            // No toWhatsApp method
        };

        $channel->send($this->account, $notification);

        Http::assertNothingSent();
    }

    #[Test]
    public function it_skips_notification_with_invalid_data(): void
    {
        Http::fake();

        $handler = app(WhatsAppNotificationHandler::class);
        $channel = new WhatsAppChannel($handler);

        // Missing required fields
        $notification = $this->createMockNotification([
            'session_id' => 'test_session_123',
            // Missing 'to' and 'message'
        ]);

        $channel->send($this->account, $notification);

        Http::assertNothingSent();
    }

    #[Test]
    public function it_works_with_real_ai_unknown_notification(): void
    {
        Http::fake([
            '*' => Http::response(['success' => true], 200),
        ]);

        $handler = app(WhatsAppNotificationHandler::class);
        $channel = new WhatsAppChannel($handler);

        // Create a real notification instance
        $messageRequest = new WhatsAppMessageRequestDTO(
            id: 'test_msg_123',
            from: '+237987654321@c.us',
            body: 'What is your return policy?',
            timestamp: time(),
            type: 'chat',
            isGroup: false
        );

        $notification = new AIUnknownInformationNotification(
            $this->account,
            $messageRequest,
            'I need more information about this topic.'
        );

        $channel->send($this->account, $notification);

        Http::assertSent(function ($request) {
            return $request['session_id'] === 'test_session_123'
                && $request['to'] === '237676636794@c.us'
                && ! empty($request['message']);
        });
    }

    private function createMockNotification(array $whatsappData): Notification
    {
        return new class($whatsappData) extends Notification
        {
            public function __construct(private array $data) {}

            public function toWhatsApp($notifiable): array
            {
                return $this->data;
            }
        };
    }
}
