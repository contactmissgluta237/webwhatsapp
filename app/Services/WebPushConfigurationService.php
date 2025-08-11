<?php

declare(strict_types=1);

namespace App\Services;

use Minishlink\WebPush\WebPush;

final class WebPushConfigurationService
{
    public function createWebPushInstance(): WebPush
    {
        return new WebPush([
            'VAPID' => [
                'subject' => config('app.url'),
                'publicKey' => config('webpush.vapid.public_key'),
                'privateKey' => config('webpush.vapid.private_key'),
            ],
        ]);
    }

    public function validateConfiguration(): bool
    {
        return ! empty(config('webpush.vapid.public_key'))
            && ! empty(config('webpush.vapid.private_key'))
            && ! empty(config('app.url'));
    }
}
