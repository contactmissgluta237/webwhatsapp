<?php

declare(strict_types=1);

namespace App\Livewire\Customer\WhatsApp\Components;

use Livewire\Component;

final class QRCodeConnectionSection extends Component
{
    public string $qrCode;
    public bool $isCheckingConnection = false;
    public ?int $connectionAttempts = null;
    public ?int $maxAttempts = null;
    public ?int $estimatedRemainingMinutes = null;
    public ?int $progressPercentage = null;

    public function confirmScanned(): void
    {
        $this->dispatch('qr-scanned-confirmed');
    }

    public function cancelConnection(): void
    {
        $this->dispatch('connection-cancelled');
    }

    public function render()
    {
        return view('livewire.customer.whats-app.components.q-r-code-connection-section');
    }
}
