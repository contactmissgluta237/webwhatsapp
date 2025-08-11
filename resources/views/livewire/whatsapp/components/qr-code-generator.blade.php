<div>
    @if($connecting)
        <div class="text-center py-4">
            <i class="la la-spinner la-spin la-3x text-primary"></i>
            <h5 class="mt-3">{{ $statusMessage }}</h5>
            <p class="text-muted">Veuillez patienter</p>
        </div>
    @elseif($qrCode)
        <div class="row">
            {{-- QR Code à gauche --}}
            <div class="col-md-6 text-center">
                <h5>{{ __('Scannez ce QR Code') }}</h5>
                <div class="my-3">
                    <img src="{{ $qrCode }}" alt="QR Code" class="img-fluid" style="max-width: 250px;">
                </div>
                
                <button type="button" class="btn btn-secondary btn-sm" wire:click="regenerateQRCode">
                    <i class="la la-refresh"></i> {{ __('Regénérer') }}
                </button>
            </div>
            
            {{-- Instructions à droite --}}
            <div class="col-md-6">
                <div class="alert alert-info h-100 d-flex flex-column justify-content-center">
                    <h6><i class="la la-info-circle"></i> Instructions :</h6>
                    <ol class="text-left mb-3">
                        <li>Ouvrez <strong>WhatsApp</strong> sur votre téléphone</li>
                        <li>Appuyez sur les <strong>3 points en haut à droite</strong></li>
                        <li>Sélectionnez <strong>Appareils connectés</strong></li>
                        <li>Appuyez sur <strong>Connecter un appareil</strong></li>
                        <li><strong>Scannez ce QR code</strong></li>
                    </ol>
                </div>
            </div>
        </div>
    @endif

    @if($statusMessage && !$connecting)
        <div class="alert alert-info mt-3">
            {{ $statusMessage }}
        </div>
    @endif
</div>

<script>
    document.addEventListener('livewire:initialized', () => {
        Livewire.on('qr-generated', (sessionId) => {
            // QR Code généré, déclencher la gestion de connexion
            Livewire.dispatch('qr-code-ready', {sessionId: sessionId});
        });
    });
</script>
