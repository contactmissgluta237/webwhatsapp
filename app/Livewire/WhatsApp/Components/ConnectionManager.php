<?php

declare(strict_types=1);

namespace App\Livewire\WhatsApp\Components;

use App\Models\WhatsAppAccount;
use App\Services\WhatsApp\WhatsAppQRService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class ConnectionManager extends Component
{
    public $tempSessionId = null;
    public $sessionName = '';
    public $waitingForConnection = false;
    public $connectionAttempts = 0;
    public $maxConnectionAttempts = 6;
    public $statusMessage = '';

    protected $listeners = [
        'check-connection-status' => 'checkConnectionStatus',
        'init-connection-manager' => 'initializeConnection'
    ];

    public function mount(string $sessionName = '', string $tempSessionId = null)
    {
        $this->sessionName = $sessionName;
        $this->tempSessionId = $tempSessionId;
    }

    public function initializeConnection($data): void
    {
        $this->sessionName = $data['sessionName'];
        $this->tempSessionId = $data['sessionId'];
    }

    public function confirmQRScanned(): void
    {
        if (!$this->tempSessionId) {
            $this->statusMessage = 'Erreur: Aucune session temporaire trouvée.';
            return;
        }

        $this->waitingForConnection = true;
        $this->connectionAttempts = 0;
        $this->statusMessage = 'Finalisation en cours... Ne fermez pas ce modal.';

        Log::info('Starting connection verification', [
            'user_id' => Auth::id(),
            'temp_session_id' => $this->tempSessionId,
        ]);

        $this->checkConnectionStatus();
    }

    public function checkConnectionStatus(): void
    {
        if (!$this->tempSessionId || $this->connectionAttempts >= $this->maxConnectionAttempts) {
            if ($this->connectionAttempts >= $this->maxConnectionAttempts) {
                $this->statusMessage = 'Temps d\'attente dépassé. Veuillez réessayer.';
                $this->waitingForConnection = false;
            }
            return;
        }

        $this->connectionAttempts++;

        try {
            $qrService = app(WhatsAppQRService::class);
            $result = $qrService->getSessionStatus($this->tempSessionId);

            if ($result['success'] && isset($result['data']['status'])) {
                $status = $result['data']['status'];

                if ($status === 'connected') {
                    $this->createWhatsAppAccount($result['data']);
                    return;
                }
            }

            if ($this->connectionAttempts < $this->maxConnectionAttempts) {
                $this->statusMessage = "Finalisation en cours... Tentative {$this->connectionAttempts}/{$this->maxConnectionAttempts}";
                $this->dispatch('schedule-next-check', 30000);
            }

        } catch (\Exception $e) {
            Log::error('Error checking connection status', [
                'error' => $e->getMessage(),
                'attempt' => $this->connectionAttempts,
            ]);

            if ($this->connectionAttempts < $this->maxConnectionAttempts) {
                $this->dispatch('schedule-next-check', 30000);
            }
        }
    }

    private function createWhatsAppAccount(array $sessionData): void
    {
        try {
            $phoneNumber = $sessionData['phoneNumber'] ?? null;

            if (!$phoneNumber) {
                $this->statusMessage = 'Erreur: Impossible de récupérer le numéro de téléphone.';
                $this->waitingForConnection = false;
                return;
            }

            WhatsAppAccount::create([
                'user_id' => Auth::id(),
                'session_name' => $this->sessionName,
                'session_id' => $this->tempSessionId,
                'phone_number' => $phoneNumber,
                'status' => 'connected',
            ]);

            $this->statusMessage = "✅ Connexion réussie ! Numéro: {$phoneNumber}";
            $this->waitingForConnection = false;

            Log::info('WhatsApp account created successfully', [
                'user_id' => Auth::id(),
                'session_name' => $this->sessionName,
                'phone_number' => $phoneNumber,
            ]);

            $this->dispatch('connection-successful');
            $this->dispatch('close-modal-delayed', 2000);

        } catch (\Exception $e) {
            Log::error('Error creating WhatsApp account', ['error' => $e->getMessage()]);
            $this->statusMessage = 'Erreur lors de la création du compte: ' . $e->getMessage();
            $this->waitingForConnection = false;
        }
    }

    public function render()
    {
        return view('livewire.whatsapp.components.connection-manager');
    }
}
