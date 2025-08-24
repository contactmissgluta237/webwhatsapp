<?php

declare(strict_types=1);

namespace App\Livewire\Customer\WhatsApp;

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
            $this->statusMessage = 'Aucun nom de session fourni.';
            $this->dispatch('stop-generating');

            return;
        }

        try {
            $this->statusMessage = 'Initialisation WhatsApp en cours... Cela peut prendre jusqu\'à 2 minutes.';
            $this->showQrSection = true;

            $qrService = app(WhatsAppQRService::class);
            $result = $qrService->generateQRCode($this->sessionName, Auth::id());

            if ($result['success']) {
                $this->qrCode = $result['qr_code'];
                $this->tempSessionId = $result['session_id'];
                $this->statusMessage = 'QR Code généré avec succès ! Scannez-le rapidement avec WhatsApp.';

                Log::info('QR Code generated', [
                    'user_id' => Auth::id(),
                    'session_name' => $this->sessionName,
                    'session_id' => $this->tempSessionId,
                ]);

                $this->dispatch('scroll-to-qr', ['targetId' => 'qr-code-section']);
            } else {
                $this->statusMessage = $result['message'] ?? 'Erreur lors de la génération du QR code';
                $this->showQrSection = false;
            }
        } catch (\Exception $e) {
            $this->statusMessage = 'Erreur: '.$e->getMessage().' (Vérifiez que le bridge Node.js est démarré)';
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
            $this->statusMessage = 'Erreur: Aucune session temporaire trouvée.';

            return;
        }

        $this->isWaitingConnection = true;
        $this->connectionAttempts = 0;
        $this->statusMessage = 'Vérification de la connexion en cours... Patientez quelques secondes.';

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
        $maxAttempts = 60; // 3 minutes (3s * 60 = 180s)

        Log::info('Checking connection status', [
            'session_id' => $this->tempSessionId,
            'attempt' => $this->connectionAttempts,
            'max_attempts' => $maxAttempts,
        ]);

        try {
            $qrService = app(WhatsAppQRService::class);
            $isConnected = $qrService->checkSessionConnection($this->tempSessionId);

            if ($isConnected) {
                // Session connectée ! On peut créer l'account
                $this->createConnectedAccount();

                return;
            }

            if ($this->connectionAttempts >= $maxAttempts) {
                // Timeout atteint
                $this->handleConnectionTimeout();

                return;
            }

            // Pas encore connecté, on continue à attendre
            $remainingTime = (int) ((($maxAttempts - $this->connectionAttempts) * 3) / 60);
            $this->statusMessage = "Connexion en cours... (Tentative {$this->connectionAttempts}/{$maxAttempts}) - Temps restant: ~{$remainingTime}min";

            // Programmer la prochaine vérification dans 3 secondes
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
                $this->statusMessage = "Vérification de connexion... (Erreur temporaire, tentative {$this->connectionAttempts}/{$maxAttempts})";
                $this->dispatch('check-connection-later');
            }
        }
    }

    private function createConnectedAccount(): void
    {
        try {
            // Récupérer les données du cache temporaire (stockées par le webhook)
            $tempData = cache()->get("whatsapp_temp_session_{$this->tempSessionId}");

            if ($tempData) {
                Log::info('Found temp session data from webhook', [
                    'session_id' => $this->tempSessionId,
                    'phone_number' => $tempData['phone_number'] ?? null,
                ]);
            } else {
                Log::warning('No temp session data found in cache', [
                    'session_id' => $this->tempSessionId,
                ]);
            }

            $account = WhatsAppAccount::create([
                'user_id' => Auth::id(),
                'session_name' => $this->sessionName,
                'session_id' => $this->tempSessionId,
                'status' => WhatsAppStatus::CONNECTED(),
                'phone_number' => $tempData['phone_number'] ?? null,
                'session_data' => $tempData['whatsapp_data'] ?? null,
                'last_seen_at' => now(),
            ]);

            // Nettoyer le cache temporaire
            cache()->forget("whatsapp_temp_session_{$this->tempSessionId}");

            $this->isWaitingConnection = false;
            session()->flash('success', 'Agent WhatsApp créé et connecté avec succès !');

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
            $this->statusMessage = 'Erreur lors de la création du compte: '.$e->getMessage();
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
        $this->statusMessage = 'La connexion n\'a pas pu être établie dans les temps. Veuillez générer un nouveau QR code.';

        Log::warning('Connection timeout reached', [
            'session_id' => $this->tempSessionId,
            'attempts' => $this->connectionAttempts,
            'user_id' => Auth::id(),
        ]);

        // Remettre le bouton "Générer un nouveau QR Code" visible
        $this->qrCode = null;
        $this->tempSessionId = null;
        $this->showQrSection = false;
    }

    public function cancelWaiting(): void
    {
        $this->isWaitingConnection = false;
        $this->connectionAttempts = 0;
        $this->statusMessage = 'Attente annulée. Vous pouvez générer un nouveau QR code.';

        Log::info('Connection waiting cancelled by user', [
            'session_id' => $this->tempSessionId,
            'user_id' => Auth::id(),
            'attempts_made' => $this->connectionAttempts,
        ]);

        // Reset pour permettre une nouvelle génération
        $this->qrCode = null;
        $this->tempSessionId = null;
        $this->showQrSection = false;
    }

    public function render()
    {
        return view('livewire.customer.whatsapp.create-session');
    }
}
