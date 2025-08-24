    <!-- Messages d'alerte -->
    @if($success)
        <div class="alert alert-success border-gray-light shadow-none d-flex align-items-center" role="alert">
            <i class="ti ti-circle-check fs-4 me-2"></i>
            <div>{{ $success }}</div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($error)
        <div class="alert alert-danger border-gray-light shadow-none d-flex align-items-center" role="alert">
            <i class="ti ti-alert-circle fs-4 me-2"></i>
            <div>{{ $error }}</div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <form wire:submit.prevent="createWithdrawal">
        <!-- Étape 1: Choix du montant -->
        <div class="mb-4">
            <h6 class="fw-bold mb-3">
                <span class="bg-whatsapp text-white rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 24px; height: 24px; font-size: 12px;">1</span>
                <span class="ms-2">{{ __('Choisissez le montant') }}</span>
            </h6>
            
            <div class="row g-3">
                @foreach($predefinedAmounts as $amountOption)
                    <div class="col-6 col-md-3">
                        <div class="form-check p-0">
                            <input class="form-check-input d-none" type="radio" name="amount" 
                                   id="amount_{{ $amountOption }}" value="{{ $amountOption }}" 
                                   wire:model.live="amount" @disabled($loading)>
                            <label class="form-check-label w-100" for="amount_{{ $amountOption }}">
                                <div class="card border-gray-light shadow-none text-center h-100 cursor-pointer amount-option 
                                     {{ $amount == $amountOption ? 'border-whatsapp bg-light-whatsapp' : '' }}">
                                    <div class="card-body p-3">
                                        <div class="fw-bold">{{ number_format($amountOption, 0, ',', ' ') }}</div>
                                        <small class="text-muted">{{ $this->getUserCurrency() }}</small>
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>
                @endforeach
            </div>
            
            @error('amount')
                <div class="text-danger small mt-2"><i class="ti ti-alert-circle me-1"></i>{{ $message }}</div>
            @enderror
        </div>

        <!-- Étape 2: Méthode de paiement -->
        @if($amount)
            <div class="mb-4">
                <h6 class="fw-bold mb-3">
                    <span class="bg-whatsapp text-white rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 24px; height: 24px; font-size: 12px;">2</span>
                    <span class="ms-2">{{ __('Méthode de retrait') }}</span>
                </h6>
                
                <div class="row g-3">
                    @foreach($paymentMethods as $method)
                        <div class="col-md-4">
                            <div class="form-check p-0">
                                <input class="form-check-input d-none" type="radio" name="payment_method" 
                                       id="method_{{ $method['value'] }}" value="{{ $method['value'] }}" 
                                       wire:model.live="payment_method" @disabled($loading)>
                                <label class="form-check-label w-100" for="method_{{ $method['value'] }}">
                                    <div class="card border-gray-light shadow-none text-center h-100 cursor-pointer payment-option
                                         {{ $payment_method == $method['value'] ? 'border-whatsapp bg-light-whatsapp' : '' }}">
                                        <div class="card-body p-3">
                                            <i class="ti ti-cash fs-1 text-primary mb-2"></i>
                                            <div class="fw-bold">{{ $method['label'] }}</div>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        </div>
                    @endforeach
                </div>
                
                @error('payment_method')
                    <div class="text-danger small mt-2"><i class="ti ti-alert-circle me-1"></i>{{ $message }}</div>
                @enderror
            </div>
        @endif

        <!-- Résumé des frais -->
        @if ($feeAmount !== null && $finalAmount !== null)
            <div class="mb-4 p-3 border-gray-light shadow-none rounded">
                <div class="row text-center">
                    <div class="col-4">
                        <div class="text-muted small mb-1">{{ __('Montant demandé') }}</div>
                        <div class="fs-5 fw-bold text-dark">{{ number_format($amount, 0, ',', ' ') }}</div>
                        <small class="text-muted">{{ $this->getUserCurrency() }}</small>
                    </div>
                    <div class="col-4">
                        <div class="text-muted small mb-1">{{ __('Frais') }}</div>
                        <div class="fs-5 fw-bold text-warning">{{ number_format($feeAmount, 0, ',', ' ') }}</div>
                        <small class="text-muted">{{ $this->getUserCurrency() }}</small>
                    </div>
                    <div class="col-4">
                        <div class="text-muted small mb-1">{{ __('Vous recevrez') }}</div>
                        <div class="fs-4 fw-bold text-success">{{ number_format($finalAmount, 0, ',', ' ') }}</div>
                        <small class="text-muted">{{ $this->getUserCurrency() }}</small>
                    </div>
                </div>
                
                <!-- Ligne de séparation visuelle -->
                <div class="row mt-2">
                    <div class="col-4">
                        <hr class="text-muted opacity-50">
                    </div>
                    <div class="col-4">
                        <div class="text-center">
                            <i class="ti ti-minus text-muted"></i>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="text-center">
                            <i class="ti ti-arrow-right text-success"></i>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Étape 3: Détails de retrait -->
        @if($payment_method)
            <div class="mb-4">
                <h6 class="fw-bold mb-3">
                    <span class="bg-whatsapp text-white rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 24px; height: 24px; font-size: 12px;">3</span>
                    <span class="ms-2">{{ __('Détails de retrait') }}</span>
                </h6>
                
                <div class="card border-gray-light shadow-none">
                    <div class="card-body">
                        @if(in_array($payment_method, [\App\Enums\PaymentMethod::MOBILE_MONEY()->value, \App\Enums\PaymentMethod::ORANGE_MONEY()->value]))
                            <div class="d-flex align-items-center mb-3">
                                <div class="bg-success bg-opacity-10 rounded-circle p-2 me-3">
                                    <i class="ti ti-device-mobile text-success"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0">{{ __('Retrait Mobile Money') }}</h6>
                                    <small class="text-muted">{{ __('Saisissez le numéro sur lequel vous voulez recevoir l\'argent') }}</small>
                                </div>
                            </div>
                            
                            <livewire:components.phone-input 
                                name="receiver_account"
                                label="Numéro de téléphone de réception"
                                :required="true"
                                :value="$phone_number"
                                wire:key="phone-input-{{ $payment_method }}"
                            />
                            
                        @elseif($payment_method === \App\Enums\PaymentMethod::BANK_CARD()->value)
                            <div class="d-flex align-items-center mb-3">
                                <div class="bg-primary bg-opacity-10 rounded-circle p-2 me-3">
                                    <i class="ti ti-credit-card text-primary"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0">{{ __('Retrait par Carte') }}</h6>
                                    <small class="text-muted">{{ __('Seul le numéro masqué sera conservé pour votre sécurité') }}</small>
                                </div>
                            </div>
                            
                            <livewire:components.card-output 
                                name="receiver_account"
                                label="Informations de la carte de réception"
                                :required="true"
                                :value="$card_number"
                                wire:key="card-output-{{ $payment_method }}"
                            />
                        @endif
                    </div>
                </div>
            </div>
        @endif

        <!-- Boutons d'action -->
        <div class="d-flex flex-column flex-md-row gap-3 justify-content-between align-items-center">
            <button type="button" wire:click="resetForm" class="btn btn-outline-secondary" @disabled($loading)>
                <i class="ti ti-refresh me-2"></i>
                {{ __('Réinitialiser') }}
            </button>
            
            <button type="submit" 
                    class="btn btn-whatsapp btn-lg px-4 py-2 fw-bold"
                    @disabled($loading || !$amount || !$payment_method || !$receiver_account)>
                @if($loading)
                    <span class="spinner-border spinner-border-sm me-2" role="status"></span>
                    {{ __('Traitement en cours...') }}
                @else
                    <i class="ti ti-cash me-2"></i>
                    {{ __('Retirer') }} {{ $amount ? $this->formatPrice($amount) : __('les fonds') }}
                @endif
            </button>
        </div>
    </form>
