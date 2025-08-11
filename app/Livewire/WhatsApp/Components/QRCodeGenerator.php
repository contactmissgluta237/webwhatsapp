<?php

declare(strict_types=1);

namespace App\Livewire\WhatsApp\Components;

use App\Services\WhatsApp\WhatsAppQRService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class QRCodeGenerator extends Component
{
    public $sessionName = '';
    public $qrCode = null;
    public $tempSessionId = null;
    public $statusMessage = '';
    public $connecting = false;

    private const CACHE_DURATION = 300; // 5 minutes

    protected $listeners = ['start-qr-generation' => 'handleQRGeneration'];

    public function mount(string $sessionName = '')
    {
        $this->sessionName = $sessionName;
    }

    public function handleQRGeneration($data): void
    {
        $this->sessionName = $data['sessionName'];
        $this->generateQRCode();
    }

    public function generateQRCode(): void
    {
        if (empty($this->sessionName)) {
            $this->statusMessage = 'Nom de session requis.';
            return;
        }

        $cacheKey = "whatsapp_qr_" . Auth::id() . "_" . md5($this->sessionName);
        $cachedQR = Cache::get($cacheKey);

        if ($cachedQR) {
            $this->qrCode = $cachedQR['qr_code'];
            $this->tempSessionId = $cachedQR['session_id'];
            $this->statusMessage = 'QR Code récupéré depuis le cache. Scannez-le avec WhatsApp.';
            $this->dispatch('qr-generated', $this->tempSessionId);
            return;
        }

        $this->connecting = true;
        $this->statusMessage = 'Génération du QR Code en cours...';

        try {
            $qrService = app(WhatsAppQRService::class);
            $result = $qrService->generateQRCode(Auth::id());

            if ($result['success']) {
                $this->qrCode = $result['url'];
                $this->tempSessionId = $result['session_id'];
                $this->statusMessage = 'QR Code généré. Scannez-le avec WhatsApp.';

                Cache::put($cacheKey, [
                    'qr_code' => $this->qrCode,
                    'session_id' => $this->tempSessionId,
                ], self::CACHE_DURATION);

                $this->dispatch('qr-generated', $this->tempSessionId);

                Log::info('QR Code generated successfully', [
                    'user_id' => Auth::id(),
                    'session_name' => $this->sessionName,
                ]);
            } else {
                $this->statusMessage = $result['error'] ?? 'Erreur lors de la génération du QR code';
                Log::error('QR generation failed', ['error' => $result['error'] ?? 'Unknown error']);
            }
        } catch (\Exception $e) {
            $this->statusMessage = 'Erreur: ' . $e->getMessage();
            Log::error('Exception in QR generation', ['error' => $e->getMessage()]);
        } finally {
            $this->connecting = false;
        }
    }

    public function regenerateQRCode(): void
    {
        $cacheKey = "whatsapp_qr_" . Auth::id() . "_" . md5($this->sessionName);
        Cache::forget($cacheKey);
        
        $this->reset(['qrCode', 'tempSessionId', 'statusMessage']);
        $this->generateQRCode();
    }

    public function render()
    {
        return view('livewire.whatsapp.components.qr-code-generator');
    }
}
