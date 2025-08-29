<?php

declare(strict_types=1);

namespace App\Livewire\Customer\WhatsApp;

use App\DTOs\WhatsApp\WhatsAppSessionStatusDTO;
use App\Enums\WhatsAppStatus;
use App\Models\WhatsAppAccount;
use App\Services\WhatsApp\WhatsAppQRService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

final class CreateSession extends Component
{
    public string $sessionName = '';
    public ?string $qrCode = null;
    public string $statusMessage = '';
    public ?string $tempSessionId = null;
    public bool $showQrSection = false;
    public bool $isWaitingConnection = false;
    public int $connectionAttempts = 0;

    protected $listeners = [
        'session-name-validated' => 'handleSessionNameValidated',
        'generate-qr-code' => 'generateQRCode',
        'checkConnectionStatus' => 'checkConnectionStatus',
    ];

    public function handleSessionNameValidated(string $sessionName): void
    {
        $this->sessionName = $sessionName;
    }

    public function generateQRCode(): void
    {
        Log::debug('CreateSession: generateQRCode START.');

        if (empty($this->sessionName)) {
            $this->statusMessage = 'No session name provided.';
            $this->dispatch('stop-generating');

            return;
        }

        try {
            $this->statusMessage = 'WhatsApp initialization in progress... This may take up to 2 minutes.';
            $this->showQrSection = true;

            $qrService = app(WhatsAppQRService::class);
            $result = $qrService->generateQRCode($this->sessionName, Auth::id());

            if ($result['success']) {
                $this->qrCode = $result['qr_code'];
                $this->tempSessionId = $result['session_id'];
                $this->statusMessage = 'QR Code generated successfully! Scan it quickly with WhatsApp.';

                Log::info('QR Code generated', [
                    'user_id' => Auth::id(),
                    'session_name' => $this->sessionName,
                    'session_id' => $this->tempSessionId,
                ]);

                $this->dispatch('scroll-to-qr', ['targetId' => 'qr-code-section']);
            } else {
                $this->statusMessage = $result['message'] ?? 'Error generating QR code';
                $this->showQrSection = false;
            }
        } catch (\Exception $e) {
            $this->statusMessage = 'Erreur: '.$e->getMessage().' (Check that the Node.js bridge is started)';
            $this->showQrSection = false;
            Log::error('QR generation error', [
                'user_id' => Auth::id(),
                'session_name' => $this->sessionName,
                'error' => $e->getMessage(),
            ]);
        }

        $this->dispatch('stop-generating');
        Log::debug('CreateSession: generateQRCode END.');
    }

    public function confirmQRScanned(): void
    {
        if (! $this->tempSessionId) {
            $this->statusMessage = 'Error: No temporary session found.';

            return;
        }

        $this->isWaitingConnection = true;
        $this->connectionAttempts = 0;
        $this->statusMessage = 'Connection verification in progress... Please wait a few seconds.';

        Log::info('Starting connection verification', [
            'session_id' => $this->tempSessionId,
            'user_id' => Auth::id(),
        ]);

        $this->checkConnectionStatus();
    }

    public function checkConnectionStatus(): void
    {
        if (! $this->tempSessionId || ! $this->isWaitingConnection) {
            return;
        }

        $this->connectionAttempts++;
        $maxAttempts = 60;

        Log::info('Checking connection status', [
            'session_id' => $this->tempSessionId,
            'attempt' => $this->connectionAttempts,
            'max_attempts' => $maxAttempts,
        ]);

        try {
            $qrService = app(WhatsAppQRService::class);
            $sessionStatus = $qrService->getSessionStatus($this->tempSessionId);

            if ($sessionStatus && $sessionStatus->isConnected()) {
                $this->createConnectedAccount($sessionStatus);

                return;
            }

            if ($this->connectionAttempts >= $maxAttempts) {
                $this->handleConnectionTimeout();

                return;
            }

            $remainingTime = (int) ((($maxAttempts - $this->connectionAttempts) * 3) / 60);
            $this->statusMessage = "Connection in progress... (Attempt {$this->connectionAttempts}/{$maxAttempts}) - Remaining time: ~{$remainingTime}min";

            $this->dispatch('check-connection-later');

        } catch (\Exception $e) {
            Log::error('Connection check failed', [
                'session_id' => $this->tempSessionId,
                'attempt' => $this->connectionAttempts,
                'error' => $e->getMessage(),
            ]);

            if ($this->connectionAttempts >= $maxAttempts) {
                $this->handleConnectionTimeout();
            } else {
                $this->statusMessage = "Connection verification... (Temporary error, attempt {$this->connectionAttempts}/{$maxAttempts})";
                $this->dispatch('check-connection-later');
            }
        }
    }

    private function createConnectedAccount(WhatsAppSessionStatusDTO $sessionStatus): void
    {
        try {
            // Get default AI model
            $defaultModel = \App\Models\AiModel::getDefault();

            $account = WhatsAppAccount::create([
                'user_id' => Auth::id(),
                'session_name' => $this->sessionName,
                'session_id' => $this->tempSessionId,
                'status' => WhatsAppStatus::CONNECTED(),
                'phone_number' => $sessionStatus->phoneNumber,
                'last_seen_at' => $sessionStatus->lastActivity ?? now(),
                'ai_model_id' => $defaultModel?->id,
                'agent_enabled' => false, // Agent is created inactive by default
            ]);

            $this->isWaitingConnection = false;
            session()->flash('success', 'WhatsApp Agent created and connected successfully!');

            Log::info('WhatsApp account created successfully', [
                'user_id' => Auth::id(),
                'account_id' => $account->id,
                'session_name' => $this->sessionName,
                'session_id' => $this->tempSessionId,
                'phone_number' => $account->phone_number,
                'attempts_needed' => $this->connectionAttempts,
            ]);

            $this->redirect(route('whatsapp.index'), navigate: true);

        } catch (\Exception $e) {
            $this->statusMessage = 'Error creating account: '.$e->getMessage();
            $this->isWaitingConnection = false;

            Log::error('Account creation failed', [
                'user_id' => Auth::id(),
                'session_name' => $this->sessionName,
                'session_id' => $this->tempSessionId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function handleConnectionTimeout(): void
    {
        $this->isWaitingConnection = false;
        $this->statusMessage = 'Connection could not be established in time. Please generate a new QR code.';

        Log::warning('Connection timeout reached', [
            'session_id' => $this->tempSessionId,
            'attempts' => $this->connectionAttempts,
            'user_id' => Auth::id(),
        ]);

        // Make the "Generate new QR Code" button visible again
        $this->qrCode = null;
        $this->tempSessionId = null;
        $this->showQrSection = false;
    }

    public function cancelWaiting(): void
    {
        $this->isWaitingConnection = false;
        $this->connectionAttempts = 0;
        $this->statusMessage = 'Waiting cancelled. You can generate a new QR code.';

        Log::info('Connection waiting cancelled by user', [
            'session_id' => $this->tempSessionId,
            'user_id' => Auth::id(),
            'attempts_made' => $this->connectionAttempts,
        ]);

        // Reset to allow new generation
        $this->qrCode = null;
        $this->tempSessionId = null;
        $this->showQrSection = false;
    }

    public function render()
    {
        return view('livewire.customer.whats-app.create-session');
    }
}
