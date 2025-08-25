<?php

declare(strict_types=1);

namespace App\Services\Payment\Gateways\MyCoolPay\DTOs;

use App\Services\Payment\Gateways\Shared\DTOs\GatewayPaymentResponseDTO;

final readonly class MyCoolPayPaymentResponseDTO extends GatewayPaymentResponseDTO
{
    public function __construct(
        public string $status,
        public string $transaction_ref,
        public string $action,
        public ?string $ussd = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            status: $data['status'],
            transaction_ref: $data['transaction_ref'],
            action: $data['action'],
            ussd: $data['ussd'] ?? null
        );
    }

    public function isSuccess(): bool
    {
        return $this->status === 'success';
    }

    public function getUserMessageToDisplay(): string
    {
        if (! $this->ussd) {
            return 'Transaction en cours de traitement...';
        }

        return "Veuillez valider la transaction sur votre mobile.\n".
               "Vous pouvez aussi composer: {$this->ussd}\n".
               'Notification de Digital House International en cours.';
    }
}
