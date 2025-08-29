<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Requests\WhatsApp\Webhook;

use App\Http\Requests\WhatsApp\Webhook\IncomingMessageRequest;
use Tests\Unit\Http\Requests\BaseRequestTestCase;

final class IncomingMessageRequestTest extends BaseRequestTestCase
{
    protected function getRequestClass(): string
    {
        return IncomingMessageRequest::class;
    }

    protected function getValidData(): array
    {
        return [
            'event' => 'message',
            'session_id' => 'whatsapp_session_123456',
            'session_name' => 'customer_support',
            'message' => [
                'id' => 'msg_abc123def456',
                'from' => '22612345678@c.us',
                'body' => 'Hello, I need help with my order',
                'timestamp' => now()->timestamp,
                'type' => 'text',
                'isGroup' => false,
                'contactName' => 'John Doe',
                'pushName' => 'John',
                'publicName' => 'John D',
                'displayName' => 'John Doe',
            ],
        ];
    }

    protected function getInvalidValidationCases(): array
    {
        return [
            'event required' => [
                'event' => '',
                'expected_error_field' => 'event',
            ],
            'event not string' => [
                'event' => 123,
                'expected_error_field' => 'event',
            ],
            'session_id required' => [
                'session_id' => '',
                'expected_error_field' => 'session_id',
            ],
            'session_id not string' => [
                'session_id' => 123,
                'expected_error_field' => 'session_id',
            ],
            'session_name required' => [
                'session_name' => '',
                'expected_error_field' => 'session_name',
            ],
            'session_name not string' => [
                'session_name' => 123,
                'expected_error_field' => 'session_name',
            ],
            'message required' => [
                'message' => null,
                'expected_error_field' => 'message',
            ],
            'message not array' => [
                'message' => 'not-an-array',
                'expected_error_field' => 'message',
            ],
            'message.id required' => [
                'message' => [
                    'from' => '22612345678@c.us',
                    'body' => 'Hello',
                    'timestamp' => now()->timestamp,
                    'type' => 'text',
                    'isGroup' => false,
                ],
                'expected_error_field' => 'message.id',
            ],
            'message.from required' => [
                'message' => [
                    'id' => 'msg_123',
                    'body' => 'Hello',
                    'timestamp' => now()->timestamp,
                    'type' => 'text',
                    'isGroup' => false,
                ],
                'expected_error_field' => 'message.from',
            ],
            'message.body required' => [
                'message' => [
                    'id' => 'msg_123',
                    'from' => '22612345678@c.us',
                    'timestamp' => now()->timestamp,
                    'type' => 'text',
                    'isGroup' => false,
                ],
                'expected_error_field' => 'message.body',
            ],
            'message.timestamp required' => [
                'message' => [
                    'id' => 'msg_123',
                    'from' => '22612345678@c.us',
                    'body' => 'Hello',
                    'type' => 'text',
                    'isGroup' => false,
                ],
                'expected_error_field' => 'message.timestamp',
            ],
            'message.timestamp not integer' => [
                'message' => [
                    'id' => 'msg_123',
                    'from' => '22612345678@c.us',
                    'body' => 'Hello',
                    'timestamp' => 'not-integer',
                    'type' => 'text',
                    'isGroup' => false,
                ],
                'expected_error_field' => 'message.timestamp',
            ],
            'message.type required' => [
                'message' => [
                    'id' => 'msg_123',
                    'from' => '22612345678@c.us',
                    'body' => 'Hello',
                    'timestamp' => now()->timestamp,
                    'isGroup' => false,
                ],
                'expected_error_field' => 'message.type',
            ],
            'message.isGroup required' => [
                'message' => [
                    'id' => 'msg_123',
                    'from' => '22612345678@c.us',
                    'body' => 'Hello',
                    'timestamp' => now()->timestamp,
                    'type' => 'text',
                ],
                'expected_error_field' => 'message.isGroup',
            ],
            'message.isGroup not boolean' => [
                'message' => [
                    'id' => 'msg_123',
                    'from' => '22612345678@c.us',
                    'body' => 'Hello',
                    'timestamp' => now()->timestamp,
                    'type' => 'text',
                    'isGroup' => 'not-boolean',
                ],
                'expected_error_field' => 'message.isGroup',
            ],
            'message.contactName too long' => [
                'message' => [
                    'id' => 'msg_123',
                    'from' => '22612345678@c.us',
                    'body' => 'Hello',
                    'timestamp' => now()->timestamp,
                    'type' => 'text',
                    'isGroup' => false,
                    'contactName' => str_repeat('a', 256),
                ],
                'expected_error_field' => 'message.contactName',
            ],
        ];
    }

    protected function getValidValidationCases(): array
    {
        return [
            'without optional fields' => [
                'message' => [
                    'id' => 'msg_123',
                    'from' => '22612345678@c.us',
                    'body' => 'Hello',
                    'timestamp' => now()->timestamp,
                    'type' => 'text',
                    'isGroup' => false,
                ],
            ],
            'group message' => [
                'message' => [
                    'id' => 'msg_456',
                    'from' => '22698765432@g.us',
                    'body' => 'Group message',
                    'timestamp' => now()->timestamp,
                    'type' => 'text',
                    'isGroup' => true,
                ],
            ],
            'media message' => [
                'message' => [
                    'id' => 'msg_789',
                    'from' => '22612345678@c.us',
                    'body' => 'Image description',
                    'timestamp' => now()->timestamp,
                    'type' => 'image',
                    'isGroup' => false,
                ],
            ],
            'different event type' => [
                'event' => 'message_sent',
            ],
            'different session' => [
                'session_id' => 'different_session_id',
                'session_name' => 'sales_team',
            ],
            'max optional field lengths' => [
                'message' => [
                    'id' => 'msg_123',
                    'from' => '22612345678@c.us',
                    'body' => 'Hello',
                    'timestamp' => now()->timestamp,
                    'type' => 'text',
                    'isGroup' => false,
                    'contactName' => str_repeat('a', 255),
                    'pushName' => str_repeat('b', 255),
                    'publicName' => str_repeat('c', 255),
                    'displayName' => str_repeat('d', 255),
                ],
            ],
        ];
    }
}
