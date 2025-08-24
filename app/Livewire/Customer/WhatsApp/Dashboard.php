<?php

declare(strict_types=1);

namespace App\Livewire\Customer\WhatsApp;

use App\Models\WhatsAppAccount;
use App\Services\WhatsApp\WhatsAppQRService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

final class Dashboard extends Component
{
    public bool $showConnectModal = false;
    public array $accounts = [];
    public string $sessionName = '';
    public ?string $qrCode = null;
    public string $statusMessage = '';
    public ?string $tempSessionId = null;

    protected $listeners = [
        'session-name-validated' => 'handleSessionNameValidated',
        'generate-qr-code' => 'generateQRCode',
        'connection-successful' => 'refreshAccounts',
        'close-connect-modal' => 'closeConnectModal',
    ];

    public function mount(): void
    {
        Log::debug('Dashboard: mount CALLED.');
        $this->refreshAccounts();
    }

    public function openConnectModal(): void
    {
        $this->showConnectModal = true;
        $this->resetModalState();
    }

    public function closeConnectModal(): void
    {
        $this->showConnectModal = false;
        $this->resetModalState();
        // Arrêter le loader si modal fermée
        $this->dispatch('stop-generating');
    }

    public function handleSessionNameValidated(string $sessionName): void
    {
        $this->sessionName = $sessionName;
    }

    public function generateQRCode(): void
    {
        Log::debug('Dashboard: generateQRCode START.');

        if (empty($this->sessionName)) {
            $this->statusMessage = 'Aucun nom de session fourni.';
            $this->stopGenerating(); // Arrêter le loader

            return;
        }

        try {
            $this->statusMessage = 'Génération du QR Code en cours...';

            // Cache désactivé pour les tests
            /*
            $cacheKey = "whatsapp_qr_" . Auth::id() . "_" . md5($this->sessionName);
            $cachedQR = Cache::get($cacheKey);

            if ($cachedQR) {
                $this->qrCode = $cachedQR['qr_code'];
                $this->tempSessionId = $cachedQR['session_id'];
                $this->statusMessage = 'QR Code récupéré depuis le cache (valide 5min). Scannez-le avec WhatsApp.';
                $this->stopGenerating(); // Arrêter le loader
                return;
            }
            */

            $qrService = app(WhatsAppQRService::class);
            $result = $qrService->generateQRCode($this->sessionName, Auth::id());

            if ($result['success']) {
                $this->qrCode = $result['qr_code'];
                $this->tempSessionId = $result['session_id'];
                $this->statusMessage = 'QR Code généré. Scannez-le avec WhatsApp.';

                // Cache désactivé pour les tests
                /*
                Cache::put($cacheKey, [
                    'qr_code' => $this->qrCode,
                    'session_id' => $this->tempSessionId,
                ], 300);
                */

                Log::info('QR Code generated', [
                    'user_id' => Auth::id(),
                    'session_name' => $this->sessionName,
                ]);
            } else {
                $this->statusMessage = $result['message'] ?? 'Erreur lors de la génération du QR code';
            }
        } catch (\Exception $e) {
            $this->statusMessage = 'Erreur: '.$e->getMessage();
            Log::error('QR generation error', [
                'user_id' => Auth::id(),
                'session_name' => $this->sessionName,
                'error' => $e->getMessage(),
            ]);
        }

        // IMPORTANT: Arrêter le loader dans tous les cas
        $this->stopGenerating();
        Log::debug('Dashboard: generateQRCode END.');
    }

    /**
     * Arrêter le loader du composant SessionNameInput
     */
    private function stopGenerating(): void
    {
        $this->dispatch('stop-generating');
    }

    public function confirmQRScanned(): void
    {
        if (! $this->tempSessionId) {
            $this->statusMessage = 'Erreur: Aucune session temporaire trouvée.';

            return;
        }

        try {
            $account = WhatsAppAccount::create([
                'user_id' => Auth::id(),
                'session_name' => $this->sessionName,
                'session_id' => $this->tempSessionId,
                'status' => 'connected',
                'phone_number' => null,
                'last_activity_at' => now(),
            ]);

            $this->statusMessage = 'Connexion WhatsApp confirmée avec succès !';
            $this->closeConnectModal();
            $this->refreshAccounts();

            Log::info('WhatsApp connection confirmed', [
                'user_id' => Auth::id(),
                'account_id' => $account->id,
                'session_name' => $this->sessionName,
            ]);

            $this->dispatch('connection-successful');

        } catch (\Exception $e) {
            $this->statusMessage = 'Erreur lors de la confirmation: '.$e->getMessage();
            Log::error('WhatsApp connection confirmation failed', [
                'user_id' => Auth::id(),
                'session_name' => $this->sessionName,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function disconnectAccount(int $accountId): void
    {
        try {
            $account = WhatsAppAccount::where('user_id', Auth::id())
                ->findOrFail($accountId);

            $account->update(['status' => 'disconnected']);
            $this->refreshAccounts();

            session()->flash('success', 'Compte WhatsApp déconnecté avec succès.');

        } catch (\Exception $e) {
            session()->flash('error', 'Erreur lors de la déconnexion: '.$e->getMessage());
        }
    }

    public function refreshAccounts(): void
    {
        Log::debug('Dashboard: refreshAccounts CALLED.');
        $this->accounts = WhatsAppAccount::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get()
            ->toArray();
    }

    private function resetModalState(): void
    {
        $this->sessionName = '';
        $this->qrCode = null;
        $this->statusMessage = '';
        $this->tempSessionId = null;
    }

    public function render()
    {
        return view('livewire.customer.whatsapp.dashboard');
    }
}
