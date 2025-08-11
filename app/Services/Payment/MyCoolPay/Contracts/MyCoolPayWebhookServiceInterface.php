<?php

declare(strict_types=1);

namespace App\Services\Payment\MyCoolPay\Contracts;

use App\Models\ExternalTransaction;

interface MyCoolPayWebhookServiceInterface
{
    public function processWebhook(array $webhookData): ExternalTransaction;
}
