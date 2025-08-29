<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Requests\PushSubscription;

use App\Http\Requests\PushSubscription\StorePushSubscriptionRequest;
use Tests\Unit\Http\Requests\BaseRequestTestCase;

final class StorePushSubscriptionRequestTest extends BaseRequestTestCase
{
    protected function getRequestClass(): string
    {
        return StorePushSubscriptionRequest::class;
    }

    protected function getValidData(): array
    {
        return [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/abc123def456',
            'keys' => [
                'p256dh' => 'BHxUqt32cQ5-4Q4wKv3-UcI4hI6LlZXVVmZHl7fD9J-GvfzZ2I3xQ8v1r2wBzIgqN3F4x',
                'auth' => 'xyz789abc123def456',
            ],
        ];
    }

    protected function getInvalidValidationCases(): array
    {
        return [
            'endpoint required' => [
                'endpoint' => '',
                'expected_error_field' => 'endpoint',
            ],
            'endpoint not url' => [
                'endpoint' => 'not-a-valid-url',
                'expected_error_field' => 'endpoint',
            ],
            'keys p256dh required' => [
                'keys' => [
                    'auth' => 'xyz789abc123def456',
                ],
                'expected_error_field' => 'keys.p256dh',
            ],
            'keys p256dh not string' => [
                'keys' => [
                    'p256dh' => 123456,
                    'auth' => 'xyz789abc123def456',
                ],
                'expected_error_field' => 'keys.p256dh',
            ],
            'keys auth required' => [
                'keys' => [
                    'p256dh' => 'BHxUqt32cQ5-4Q4wKv3-UcI4hI6LlZXVVmZHl7fD9J-GvfzZ2I3xQ8v1r2wBzIgqN3F4x',
                ],
                'expected_error_field' => 'keys.auth',
            ],
            'keys auth not string' => [
                'keys' => [
                    'p256dh' => 'BHxUqt32cQ5-4Q4wKv3-UcI4hI6LlZXVVmZHl7fD9J-GvfzZ2I3xQ8v1r2wBzIgqN3F4x',
                    'auth' => 123456,
                ],
                'expected_error_field' => 'keys.auth',
            ],
            'missing keys' => [
                'keys' => null,
                'expected_error_field' => 'keys.p256dh',
            ],
        ];
    }

    protected function getValidValidationCases(): array
    {
        return [
            'mozilla push endpoint' => [
                'endpoint' => 'https://updates.push.services.mozilla.com/wpush/v2/abc123',
            ],
            'chrome push endpoint' => [
                'endpoint' => 'https://android.googleapis.com/gcm/send/def456',
            ],
            'different keys format' => [
                'keys' => [
                    'p256dh' => 'BI2dOTdC_r6rChv3Ujy0n9H5v3TiKlVGGJ9FbPHUYCE_M7QtzxUePwvx1Q',
                    'auth' => 'short_auth_key',
                ],
            ],
            'long keys' => [
                'keys' => [
                    'p256dh' => str_repeat('a', 100),
                    'auth' => str_repeat('b', 50),
                ],
            ],
        ];
    }
}
