<div>
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
            
            {{-- Expiration timer --}}
            <div class="text-center mt-3">
                <p class="text-muted">
                    <i class="la la-clock-o"></i> 
                    {{ __('Le QR Code expire dans 5 minutes') }}
                </p>
            </div>
        </div>

        {{-- Instructions Section --}}
        <div class="col-lg-6">
            @if($isCheckingConnection)
                {{-- Connection Status --}}
                <div class="connection-status-card">
                    <div class="text-center">
                        <div class="loading-animation mb-4">
                            <i class="la la-spinner la-spin la-3x text-whatsapp"></i>
                        </div>
                        
                        <h5 class="mb-3">{{ __('Connexion en cours...') }}</h5>
                        
                        @if($progressPercentage !== null)
                            <div class="progress mb-3" style="height: 10px;">
                                <div class="progress-bar progress-bar-striped progress-bar-animated bg-whatsapp" 
                                     role="progressbar" 
                                     style="width: {{ $progressPercentage }}%">
                                </div>
                            </div>
                        @endif
                        
                        @if($connectionAttempts && $maxAttempts)
                            <p class="text-muted mb-3">
                                {{ __('Tentative') }} {{ $connectionAttempts }}/{{ $maxAttempts }}
                                @if($estimatedRemainingMinutes > 0)
                                    - {{ __('~') }}{{ $estimatedRemainingMinutes }}{{ __('min restantes') }}
                                @endif
                            </p>
                        @endif

                        <p class="small text-muted mb-4">
                            {{ __('Patientez pendant que nous vérifions votre connexion WhatsApp...') }}
                        </p>

                        <button wire:click="cancelConnection" 
                                class="btn btn-outline-secondary">
                            <i class="la la-times"></i> {{ __('Annuler') }}
                        </button>
                    </div>
                </div>
            @else
                {{-- Instructions Panel --}}
                <div class="instructions-panel">
                    <h5 class="mb-4">
                        <i class="la la-mobile text-success"></i>
                        {{ __('Instructions') }}
                    </h5>

                    <div class="instruction-step mb-3">
                        <div class="step-icon">1</div>
                        <div class="step-content">
                            <strong>{{ __('Ouvrez WhatsApp') }}</strong><br>
                            <small class="text-muted">{{ __('Sur votre téléphone') }}</small>
                        </div>
                    </div>

                    <div class="instruction-step mb-3">
                        <div class="step-icon">2</div>
                        <div class="step-content">
                            <strong>{{ __('Allez dans Paramètres') }}</strong><br>
                            <small class="text-muted">{{ __('Puis "Appareils connectés"') }}</small>
                        </div>
                    </div>

                    <div class="instruction-step mb-3">
                        <div class="step-icon">3</div>
                        <div class="step-content">
                            <strong>{{ __('Connecter un appareil') }}</strong><br>
                            <small class="text-muted">{{ __('Scannez le QR code') }}</small>
                        </div>
                    </div>

                    <div class="instruction-step mb-4">
                        <div class="step-icon">4</div>
                        <div class="step-content">
                            <strong>{{ __('Confirmez') }}</strong><br>
                            <small class="text-muted">{{ __('Cliquez "J\'ai scanné" ci-dessous') }}</small>
                        </div>
                    </div>

                    <div class="text-center">
                        <button wire:click="confirmScanned" 
                                class="btn btn-success btn-lg btn-block mb-2">
                            <i class="la la-check"></i> {{ __('J\'ai scanné le QR Code') }}
                        </button>
                        
                        <button wire:click="cancelConnection" 
                                class="btn btn-outline-secondary">
                            <i class="la la-times"></i> {{ __('Annuler') }}
                        </button>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>