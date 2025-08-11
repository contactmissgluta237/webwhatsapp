<?php

use App\Enums\PaymentMethod;

?>

<div class="container-fluid">
    <form wire:submit.prevent="createRecharge">
        <div class="row">
            <div class="col-md-12 mb-3">
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
                <div class="col-md-12 mb-4">
                    @if(in_array($payment_method, [PaymentMethod::MOBILE_MONEY()->value, PaymentMethod::ORANGE_MONEY()->value]))
                        <livewire:components.phone-input 
                            name="sender_account"
                            label="Votre numéro de téléphone"
                            :required="true"
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
            
            <button type="submit" class="btn btn-success btn-lg" @disabled($loading || !$amount || !$payment_method || !$sender_account)>
                @if($loading)
                    <span class="spinner-border spinner-border-sm me-2" role="status"></span>
                    Recharge en cours...
                @else
                    <i class="ti ti-credit-card me-2"></i>
                    Recharger {{ $amount ? number_format($amount, 0, ',', ' ') . ' FCFA' : 'mon compte' }}
                @endif
            </button>
        </div>
    </form>
</div>