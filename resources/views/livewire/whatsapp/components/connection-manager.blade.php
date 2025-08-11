<div>
    @if($tempSessionId)
        <div class="text-center mt-4">
            @if(!$waitingForConnection)
                <button type="button" class="btn btn-success" wire:click="confirmQRScanned">
                    <i class="la la-check"></i> {{ __('QR Code scanné - Confirmer') }}
                </button>
            @else
                <div class="alert alert-warning">
                    <div class="d-flex align-items-center justify-content-center">
                        <i class="la la-spinner la-spin mr-2"></i>
                        <div>
                            <strong>Finalisation en cours...</strong><br>
                            <small>Ne fermez pas ce modal. Tentative {{ $connectionAttempts }}/{{ $maxConnectionAttempts }}</small>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    @endif

    @if($statusMessage)
        <div class="alert alert-info mt-3">
            {{ $statusMessage }}
        </div>
    @endif
</div>

<script>
    document.addEventListener('livewire:initialized', () => {
        let checkTimeout;

        Livewire.on('schedule-next-check', (delay) => {
            if (checkTimeout) clearTimeout(checkTimeout);
            checkTimeout = setTimeout(() => {
                Livewire.dispatch('check-connection-status');
            }, delay);
        });

        Livewire.on('close-modal-delayed', (delay) => {
            setTimeout(() => {
                Livewire.dispatch('close-connect-modal');
            }, delay);
        });

        Livewire.on('connection-successful', () => {
            if (checkTimeout) clearTimeout(checkTimeout);
        });
    });
</script>
