<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Requests\PushSubscription;

use App\Http\Requests\PushSubscription\DestroyPushSubscriptionRequest;
use Tests\Unit\Http\Requests\BaseRequestTestCase;

final class DestroyPushSubscriptionRequestTest extends BaseRequestTestCase
{
    protected function getRequestClass(): string
    {
        return DestroyPushSubscriptionRequest::class;
    }

    protected function getValidData(): array
    {
        return [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/abc123def456',
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
            'endpoint not string' => [
                'endpoint' => 123456,
                'expected_error_field' => 'endpoint',
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
            'safari push endpoint' => [
                'endpoint' => 'https://web.push.apple.com/push/v1/xyz789',
            ],
            'edge push endpoint' => [
                'endpoint' => 'https://wns2-par02p.notify.windows.com/w/?token=abc123',
            ],
            'long endpoint url' => [
                'endpoint' => 'https://fcm.googleapis.com/fcm/send/'.str_repeat('a', 100),
            ],
        ];
    }
}
