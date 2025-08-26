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
        $this->isValid = false; // Reset par défaut

        if (empty($this->sessionName)) {
            return; // Pas d'erreur si vide, juste pas valide
        }

        // Validation du format seulement (pas d'unicité)
        if (strlen($this->sessionName) < 3) {
            $this->errorMessage = 'Le nom doit contenir au moins 3 caractères.';

            return;
        }

        if (strlen($this->sessionName) > 50) {
            $this->errorMessage = 'Le nom ne peut pas dépasser 50 caractères.';

            return;
        }

        // Si on arrive ici, c'est valide
        $this->isValid = true;
    }

    public function generateQRCode(): void
    {
        Log::debug('SessionNameInput: generateQRCode CALLED.');

        // Double vérification avant génération
        $this->validateSessionName();

        if (! $this->isValid || empty($this->sessionName)) {
            $this->errorMessage = 'Veuillez saisir un nom de session valide.';

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
