<?php

declare(strict_types=1);

namespace App\Livewire\Customer\WhatsApp;

use App\DTOs\WhatsApp\ConnectionVerificationDTO;
use App\DTOs\WhatsApp\QRRefreshResultDTO;
use App\Handlers\WhatsApp\RefreshConnectionHandler;
use App\Handlers\WhatsApp\RefreshQRHandler;
use App\Models\WhatsAppAccount;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

final class RefreshSession extends Component
{
    public WhatsAppAccount $account;
    public ?QRRefreshResultDTO $qrResult = null;
    public ?ConnectionVerificationDTO $connectionResult = null;
    public bool $isGenerating = false;
    public bool $isCheckingConnection = false;
    public int $connectionAttempts = 0;

    public function mount(WhatsAppAccount $account): void
    {
        if ($account->user_id !== Auth::id()) {
            abort(403);
        }
        $this->account = $account;
    }

    #[Computed]
    public function sessionInfo(): WhatsAppAccount
    {
        return $this->account;
    }

    public function generateQR(): void
    {
        $this->isGenerating = true;
        $this->reset(['qrResult', 'connectionResult', 'connectionAttempts']);

        try {
            $handler = app(RefreshQRHandler::class);
            $this->qrResult = $handler->handle($this->account);

            $this->dispatch('qr-generated');
        } catch (\Exception $e) {
            $this->dispatch('refresh-error', ['message' => $e->getMessage()]);
        } finally {
            $this->isGenerating = false;
        }
    }

    #[On('qr-scanned-confirmed')]
    public function confirmScanned(): void
    {
        if (! $this->qrResult || $this->qrResult->isExpired()) {
            $this->dispatch('refresh-error', ['message' => 'QR Code expired']);

            return;
        }

        $this->isCheckingConnection = true;
        $this->connectionAttempts = 0;
        $this->checkConnection();
    }

    #[On('check-connection')]
    public function checkConnection(): void
    {
        if (! $this->isCheckingConnection || ! $this->qrResult) {
            return;
        }

        $this->connectionAttempts++;

        try {
            $handler = app(RefreshConnectionHandler::class);
            $this->connectionResult = $handler->handle(
                $this->account,
                $this->qrResult->sessionId,
                $this->connectionAttempts
            );

            if ($this->connectionResult->isConnected) {
                session()->flash('success', 'WhatsApp session refreshed successfully!');
                $this->redirectRoute('whatsapp.index');
            } elseif ($this->connectionResult->shouldContinue) {
                $this->dispatch('schedule-next-check', [
                    'interval' => $this->connectionResult->nextIntervalSeconds * 1000,
                ]);
            } else {
                $this->handleTimeout();
            }
        } catch (\Exception $e) {
            $this->dispatch('refresh-error', ['message' => $e->getMessage()]);
            $this->resetConnectionState();
        }
    }

    #[On('connection-cancelled')]
    public function cancelRefresh(): void
    {
        if ($this->qrResult) {
            $handler = app(RefreshQRHandler::class);
            $handler->clearCache($this->account->id);
        }

        $this->reset(['qrResult', 'connectionResult', 'isCheckingConnection', 'connectionAttempts']);
        $this->dispatch('refresh-cancelled');
    }

    private function handleTimeout(): void
    {
        $this->dispatch('connection-timeout');
        $this->resetConnectionState();
    }

    private function resetConnectionState(): void
    {
        $this->isCheckingConnection = false;
        $this->connectionResult = null;
    }

    public function render()
    {
        return view('livewire.customer.whatsapp.refresh-session');
    }
}
