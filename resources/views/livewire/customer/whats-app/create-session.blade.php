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
                        @livewire('customer.whats-app.components.q-r-instructions-panel', [
                            'isCheckingConnection' => $isWaitingConnection,
                            'connectionResult' => null,
                            'confirmButtonText' => "J'ai scanné le QR code",
                            'cancelButtonText' => 'Annuler et générer un nouveau QR'
                        ])
                        
                        @if($statusMessage)
                            <div class="alert alert-whatsapp mt-3" role="alert">
                                <i class="la {{ $isWaitingConnection ? 'la-clock-o' : 'la-check-circle' }}"></i> {{ $statusMessage }}
                            </div>
                        @endif
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
