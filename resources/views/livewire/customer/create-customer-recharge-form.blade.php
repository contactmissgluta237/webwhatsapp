<div class="container-fluid">
    @if($success)
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            {{ $success }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if($error)
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>
            {{ $error }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <form wire:submit.prevent="createRecharge">
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="amount" class="form-label">Montant à recharger <span class="text-danger">*</span></label>
                <div class="input-group">
                    <span class="input-group-text">FCFA</span>
                    <select class="form-select @error('amount') is-invalid @enderror" 
                            wire:model.live="amount" id="amount" @disabled($loading)>
                        <option value="">-- Sélectionnez un montant --</option>
                        @foreach($predefinedAmounts as $amountOption)
                            <option value="{{ $amountOption }}">
                                {{ number_format($amountOption, 0, ',', ' ') }}
                            </option>
                        @endforeach
                                        </select>
                </div>
                @if ($feeAmount !== null && $totalToPay !== null)
                    <div class="mt-2 text-info">
                        Frais de recharge : <strong>{{ number_format($feeAmount, 2, ',', ' ') }} FCFA</strong>. Vous paierez au total : <strong>{{ number_format($totalToPay, 2, ',', ' ') }} FCFA</strong>.
                    </div>
                @endif

                @error('amount')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-6 mb-3">
                <label for="payment_method" class="form-label">Méthode de Paiement <span class="text-danger">*</span></label>
                <select class="form-select @error('payment_method') is-invalid @enderror" 
                        wire:model.live="payment_method" id="payment_method" @disabled($loading)>
                    <option value="">-- Choisissez votre opérateur --</option>
                    @foreach($paymentMethods as $method)
                        <option value="{{ $method['value'] }}">
                            {{ $method['label'] }}
                        </option>
                    @endforeach
                </select>
                @error('payment_method')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        @if($payment_method)
            <div class="row">
                <div class="col-md-8 mb-4">
                    @if(in_array($payment_method, [PaymentMethod::MOBILE_MONEY()->value, PaymentMethod::ORANGE_MONEY()->value]))
                        <livewire:components.phone-input 
                            name="sender_account"
                            label="Votre numéro de téléphone"
                            :required="true"
                            :value="$sender_account"
                            :default-country-id="$country_id"
                            wire:key="phone-input-{{ $payment_method }}"
                        />
                        <small class="form-text text-muted">
                            Le numéro depuis lequel le retrait sera effectué automatiquement
                        </small>
                    @elseif($payment_method === PaymentMethod::BANK_CARD()->value)
                        <livewire:components.card-input 
                            name="sender_account"
                            label="Informations de la carte"
                            :required="true"
                            wire:key="card-input-{{ $payment_method }}"
                        />
                        <small class="form-text text-muted">
                            Seul le numéro masqué sera conservé pour votre sécurité
                        </small>
                    @endif
                </div>
            </div>
        @endif

        <div class="d-flex justify-content-between align-items-center">
            <button type="button" wire:click="resetForm" class="btn btn-outline-secondary" @disabled($loading)>
                <i class="ti ti-refresh"></i>
                Réinitialiser
            </button>
            
            <button type="submit" class="btn btn-whatsapp btn-lg" @disabled($loading || !$amount || !$payment_method || !$sender_account)>
                @if($loading)
                    <span class="spinner-border spinner-border-sm me-2" role="status"></span>
                    Retrait en cours...
                @else
                    <i class="ti ti-credit-card me-2"></i>
                    Recharger {{ $amount ? number_format($amount, 0, ',', ' ') . ' FCFA' : 'mon compte' }}
                @endif
            </button>
        </div>
    </form>
</div>