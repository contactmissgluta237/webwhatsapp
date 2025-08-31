<?php

declare(strict_types=1);

namespace App\Livewire\Customer\WhatsApp\Components;

use Livewire\Component;

final class QRInstructionsPanel extends Component
{
    public bool $isCheckingConnection = false;
    public ?object $connectionResult = null;
    public string $confirmButtonText = "J'ai scanné le QR Code";
    public string $cancelButtonText = 'Annuler';

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
        return view('livewire.customer.whats-app.components.q-r-instructions-panel');
    }
}
