<?php

declare(strict_types=1);

namespace App\Livewire\Customer\WhatsApp\Components;

use Illuminate\Support\Facades\Log;
use Livewire\Component;

final class SessionNameInput extends Component
{
    public string $sessionName = '';
    public bool $isValid = false;
    public string $errorMessage = '';
    public bool $isGenerating = false;

    protected $listeners = [
        'stop-generating' => 'stopGenerating',
    ];

    public function updatedSessionName(): void
    {
        Log::debug('SessionNameInput: updatedSessionName CALLED.');
        $this->validateSessionName();

        if ($this->isValid) {
            $this->dispatch('session-name-validated', $this->sessionName);
        }
    }

    public function validateSessionName(): void
    {
        $this->errorMessage = '';
        $this->isValid = false; // Reset by default

        if (empty($this->sessionName)) {
            return; // No error if empty, just not valid
        }

        // Format validation only (no uniqueness)
        if (strlen($this->sessionName) < 3) {
            $this->errorMessage = 'The name must contain at least 3 characters.';

            return;
        }

        if (strlen($this->sessionName) > 50) {
            $this->errorMessage = 'The name cannot exceed 50 characters.';

            return;
        }

        // If we get here, it's valid
        $this->isValid = true;
    }

    public function generateQRCode(): void
    {
        Log::debug('SessionNameInput: generateQRCode CALLED.');

        // Double check before generation
        $this->validateSessionName();

        if (! $this->isValid || empty($this->sessionName)) {
            $this->errorMessage = 'Please enter a valid session name.';

            return;
        }

        $this->isGenerating = true;
        $this->dispatch('generate-qr-code');
    }

    public function stopGenerating(): void
    {
        Log::debug('SessionNameInput: stopGenerating CALLED.');
        $this->isGenerating = false;
    }

    public function getSessionName(): string
    {
        return $this->sessionName;
    }

    public function render()
    {
        return view('livewire.customer.whats-app.components.session-name-input');
    }
}
