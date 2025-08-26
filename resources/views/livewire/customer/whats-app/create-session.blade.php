<div>
@if($showQrSection)
<div class="row">
    <div class="col-lg-12">
        <div class="form-section">
            <div class="d-flex align-items-center mb-4">
                <div class="step-number">2</div>
                <h4 class="mb-0">{{ __('Connexion WhatsApp') }}</h4>
            </div>

            @if($statusMessage && !$qrCode)
                <div class="alert alert-whatsapp" role="alert">
                    <i class="la la-info-circle"></i> {{ $statusMessage }}
                </div>
            @endif

            @if($qrCode)
                <div class="row">
                    {{-- QR Code Section --}}
                    <div class="col-lg-6" id="qr-code-section">
                        <div class="qr-container text-center">
                            <h5 class="text-white mb-3">
                                <i class="la la-qrcode"></i> {{ __('Votre QR Code') }}
                            </h5>
                            <div class="qr-code-display">
                                <div style="width: 100%; max-width: 300px; margin: 0 auto;">
                                    {!! \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')->size(280)->generate($qrCode) !!}
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Instructions Section --}}
                    <div class="col-lg-6">
                        <div class="instructions-panel h-100">
                            <h5 class="mb-4">
                                <i class="la la-mobile text-success"></i> {{ __('Instructions de connexion') }}
                            </h5>
                            
                            <div class="mb-4">
                                <div class="d-flex align-items-start mb-3">
                                    <div class="step-number">1</div>
                                    <div>
                                        <strong>Ouvrez WhatsApp</strong> sur votre téléphone
                                    </div>
                                </div>
                                
                                <div class="d-flex align-items-start mb-3">
                                    <div class="step-number">2</div>
                                    <div>
                                        Allez au <strong>Menu (⋮)</strong> puis <strong>Appareils connectés</strong>
                                    </div>
                                </div>
                                
                                <div class="d-flex align-items-start mb-3">
                                    <div class="step-number">3</div>
                                    <div>
                                        Appuyez sur <strong>Connecter un appareil</strong>
                                    </div>
                                </div>
                                
                                <div class="d-flex align-items-start mb-4">
                                    <div class="step-number">4</div>
                                    <div>
                                        <strong>Scannez le QR code</strong> affiché à gauche
                                    </div>
                                </div>
                            </div>

                            <div class="text-center">
                                @if($isWaitingConnection)
                                    <div class="mb-3">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="sr-only">Vérification...</span>
                                        </div>
                                        <p class="mt-2 text-muted">Vérification de la connexion en cours...</p>
                                    </div>
                                    <button type="button" class="btn btn-warning btn-lg" disabled>
                                        <i class="la la-clock-o"></i> Connexion en cours...
                                    </button>
                                    <div class="mt-3">
                                        <button type="button" class="btn btn-outline-secondary" wire:click="cancelWaiting">
                                            <i class="la la-times"></i> Annuler et générer un nouveau QR
                                        </button>
                                    </div>
                                @else
                                    <button type="button" class="btn btn-whatsapp btn-lg" wire:click="confirmQRScanned">
                                        <i class="la la-check-circle"></i> {{ "J'ai scanné le QR code" }}
                                    </button>
                                @endif
                            </div>

                            @if($statusMessage)
                                <div class="alert alert-whatsapp mt-3" role="alert">
                                    <i class="la {{ $isWaitingConnection ? 'la-clock-o' : 'la-check-circle' }}"></i> {{ $statusMessage }}
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endif
</div>

<script>
document.addEventListener('livewire:init', () => {
    Livewire.on('check-connection-later', () => {
        // Programmer la prochaine vérification dans 3 secondes
        setTimeout(() => {
            Livewire.dispatch('checkConnectionStatus');
        }, 3000);
    });
});
</script>
