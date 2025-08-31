<div>
    {{-- Generate QR Button --}}
    @if(!$qrResult && !$isCheckingConnection)
        <button wire:click="generateQR" 
                wire:loading.attr="disabled"
                wire:target="generateQR"
                class="btn btn-whatsapp btn-lg px-3">
            <span wire:loading.remove wire:target="generateQR">
                <i class="la la-refresh mr-1"></i> {{ __('Générer QR Code') }}
            </span>
            <span wire:loading wire:target="generateQR">
                <i class="la la-spinner la-spin mr-1"></i> {{ __('Génération...') }}
            </span>
        </button>
    @endif

    {{-- QR Code Section --}}
    @if($qrResult)
    <div class="mt-4">
        <div class="card-modern">
            <div class="card-header bg-whatsapp-light text-white">
                <h4 class="mb-0">
                    <i class="la la-qrcode mr-2"></i> {{ __('Connexion WhatsApp') }}
                </h4>
            </div>
            <div class="card-body">
                <div class="row">
                    {{-- QR Code Section --}}
                    <div class="col-lg-6" id="qr-code-section">
                        <div class="qr-container text-center">
                            <div class="hero-content">
                                <h5 class="text-white mb-3">
                                    <i class="la la-qrcode mr-2"></i> {{ __('Votre QR Code') }}
                                </h5>
                                <div class="qr-code-display">
                                    <div style="width: 100%; max-width: 300px; margin: 0 auto;">
                                        {!! \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')->size(280)->generate($qrResult->qrCode) !!}
                                    </div>
                                </div>
                                
                                {{-- Expiration timer --}}
                                <div class="mt-3">
                                    <p class="text-white mb-0">
                                        <i class="la la-clock-o mr-1"></i> 
                                        {{ __('Le QR Code expire dans 5 minutes') }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

        {{-- Instructions Section --}}
        <div class="col-lg-6">
            @livewire('customer.whats-app.components.q-r-instructions-panel', [
                'isCheckingConnection' => $isCheckingConnection,
                'connectionResult' => $connectionResult,
                'confirmButtonText' => "J'ai scanné le QR Code",
                'cancelButtonText' => 'Annuler'
            ])
        </div>
                    </div>
                </div>
            </div>
        @endif
</div>

@push('scripts')
<script>
    document.addEventListener('livewire:init', function () {
        Livewire.on('qr-generated', function () {
            // Scroll to QR code section smoothly
            setTimeout(() => {
                const qrSection = document.getElementById('qr-code-section');
                if (qrSection) {
                    qrSection.scrollIntoView({ 
                        behavior: 'smooth',
                        block: 'center'
                    });
                }
            }, 500);
        });

        Livewire.on('refresh-error', function (event) {
            alert('❌ ' + event.message);
        });

        Livewire.on('connection-timeout', function () {
            alert('{{ __("⏱️ Délai dépassé. Veuillez générer un nouveau QR code.") }}');
        });

        Livewire.on('schedule-next-check', function (event) {
            setTimeout(() => {
                Livewire.dispatch('check-connection');
            }, event.interval);
        });
    });
</script>
@endpush