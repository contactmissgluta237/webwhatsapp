<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Requests\Api\WhatsApp;

use App\Http\Requests\Api\WhatsApp\SessionStatusWebhookRequest;
use Tests\Unit\Http\Requests\BaseRequestTestCase;

final class SessionStatusWebhookRequestTest extends BaseRequestTestCase
{
    protected function getRequestClass(): string
    {
        return SessionStatusWebhookRequest::class;
    }

    protected function getValidData(): array
    {
        return [
            'session_id' => 'whatsapp_session_123456789',
            'status' => 'connected',
            'phone_number' => '+22612345678',
        ];
    }

    protected function getInvalidValidationCases(): array
    {
        return [
            'session_id required' => [
                'session_id' => '',
                'expected_error_field' => 'session_id',
            ],
            'session_id not string' => [
                'session_id' => 123456,
                'expected_error_field' => 'session_id',
            ],
            'status required' => [
                'status' => '',
                'expected_error_field' => 'status',
            ],
            'status not string' => [
                'status' => 123,
                'expected_error_field' => 'status',
            ],
            'phone_number not string' => [
                'phone_number' => 123456789,
                'expected_error_field' => 'phone_number',
            ],
        ];
    }

    protected function getValidValidationCases(): array
    {
        return [
            'without phone number' => [
                'phone_number' => null,
            ],
            'disconnected status' => [
                'status' => 'disconnected',
            ],
            'connecting status' => [
                'status' => 'connecting',
            ],
            'error status' => [
                'status' => 'error',
            ],
            'different session format' => [
                'session_id' => 'session_abc_def_123',
            ],
            'different phone format' => [
                'phone_number' => '22612345678',
            ],
            'international phone format' => [
                'phone_number' => '+1-555-123-4567',
            ],
        ];
    }
}
