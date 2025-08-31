<div>
    @if($isCheckingConnection)
        {{-- Connection Status --}}
        <div class="connection-status-card">
            <div class="text-center">
                <div class="loading-animation mb-4">
                    <i class="la la-spinner la-spin la-3x text-whatsapp"></i>
                </div>
                
                <h4 class="mb-3 text-whatsapp">{{ __('Vérification de la connexion...') }}</h4>
                
                @if($connectionResult)
                    <div class="progress mb-4">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" 
                             role="progressbar" 
                             style="width: {{ $connectionResult->getProgressPercentage() }}%">
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted">{{ __('Progression') }}:</span>
                            <span class="font-weight-bold">{{ $connectionResult->getProgressPercentage() }}%</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted">{{ __('Tentative') }}:</span>
                            <span class="font-weight-bold">{{ $connectionResult->attempts }}/60</span>
                        </div>
                        @if($connectionResult->getEstimatedRemainingMinutes() > 0)
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-muted">{{ __('Temps restant') }}:</span>
                                <span class="font-weight-bold">~{{ $connectionResult->getEstimatedRemainingMinutes() }} min</span>
                            </div>
                        @endif
                    </div>
                @endif

                <p class="text-muted mb-4">
                    {{ __('Nous vérifions que votre téléphone WhatsApp est bien connecté...') }}
                </p>

                <button wire:click="cancelConnection" 
                        class="btn btn-outline-secondary">
                    <i class="la la-times mr-1"></i> {{ $cancelButtonText }}
                </button>
            </div>
        </div>
    @else
        {{-- Instructions Panel --}}
        <div class="instructions-panel">
            <div class="mb-3 text-center">
                <h3 class="mb-2 text-whatsapp font-weight-bold">
                    <i class="la la-mobile mr-2"></i>
                    {{ __('Instructions de connexion') }}
                </h3>
                <p class="text-muted mb-0">{{ __('Suivez ces étapes pour reconnecter votre session WhatsApp') }}</p>
            </div>

            <div class="instruction-step">
                <div class="step-content">
                    <div class="step-title"><strong>1 - {{ __('Ouvrez WhatsApp') }}</strong></div>
                    <div class="step-description">{{ __('Sur votre téléphone mobile') }}</div>
                </div>
            </div>

            <div class="instruction-step">
                <div class="step-content">
                    <div class="step-title"><strong>2 - {{ __('Allez dans Paramètres') }}</strong></div>
                    <div class="step-description">{{ __('Menu ⋮ puis "Appareils connectés"') }}</div>
                </div>
            </div>

            <div class="instruction-step">
                <div class="step-content">
                    <div class="step-title"><strong>3 - {{ __('Connecter un appareil') }}</strong></div>
                    <div class="step-description">{{ __('Scannez le QR code affiché à gauche') }}</div>
                </div>
            </div>

            <div class="instruction-step mb-3">
                <div class="step-content">
                    <div class="step-title"><strong>4 - {{ __('Confirmez la connexion') }}</strong></div>
                    <div class="step-description">{{ __('Cliquez sur le bouton ci-dessous une fois scanné') }}</div>
                </div>
            </div>

            <div class="text-center">
                <div class="mb-3">
                    <button wire:click="confirmScanned" 
                            class="btn btn-whatsapp btn-lg w-100 mb-2">
                        <i class="la la-check mr-2"></i> {{ $confirmButtonText }}
                    </button>
                    <button wire:click="cancelConnection" 
                            class="btn btn-outline-secondary btn-lg w-100">
                        <i class="la la-times mr-1"></i> {{ $cancelButtonText }}
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>