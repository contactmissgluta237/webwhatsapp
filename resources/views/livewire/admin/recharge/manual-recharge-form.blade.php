@php
use App\Enums\PaymentMethod;
@endphp

<div class="container-fluid">
    <form wire:submit.prevent="createRecharge">
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="external_transaction_id" class="form-label">ID Transaction Externe <span class="text-danger">*</span></label>
                <input type="text" 
                       class="form-control @error('external_transaction_id') is-invalid @enderror" 
                       wire:model="external_transaction_id"
                       id="external_transaction_id"
                       placeholder="Ex: TRX123456789">
                @error('external_transaction_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-6 mb-3">
                <label for="payment_method" class="form-label">Méthode de Paiement <span class="text-danger">*</span></label>
                <select class="form-select @error('payment_method') is-invalid @enderror" 
                        wire:model.live="payment_method" id="payment_method">
                    <option value="">-- Sélectionnez une méthode --</option>
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

        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="sender_name" class="form-label">Nom Expéditeur <span class="text-danger">*</span></label>
                <input type="text" 
                       class="form-control @error('sender_name') is-invalid @enderror" 
                       wire:model="sender_name"
                       id="sender_name"
                       placeholder="Ex: Jean Dupont">
                @error('sender_name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-6 mb-3">
                @if($payment_method && in_array($payment_method, [PaymentMethod::MOBILE_MONEY()->value, PaymentMethod::ORANGE_MONEY()->value]))
                    <livewire:components.phone-input 
                        name="sender_account"
                        label="Compte Expéditeur"
                        :required="true"
                        wire:key="phone-input-{{ $payment_method }}"
                    />
                @elseif($payment_method === PaymentMethod::BANK_CARD()->value)
                    <livewire:components.card-output 
                        name="sender_account"
                        label="Compte Expéditeur"
                        :required="true"
                        :value="$sender_account"
                        wire:key="card-output-{{ $payment_method }}"
                    />
                @elseif($payment_method)
                    <label for="sender_account" class="form-label">Compte Expéditeur <span class="text-danger">*</span></label>
                    <input type="text" 
                           class="form-control @error('sender_account') is-invalid @enderror" 
                           wire:model="sender_account"
                           id="sender_account"
                           placeholder="Ex: +237670000000">
                @else
                    <label for="sender_account" class="form-label">Compte Expéditeur <span class="text-danger">*</span></label>
                    <div class="form-control-plaintext text-muted">
                        Sélectionnez d'abord une méthode de paiement
                    </div>
                @endif
                
                @error('sender_account')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="receiver_name" class="form-label">Nom Destinataire <span class="text-danger">*</span></label>
                <input type="text" 
                       class="form-control @error('receiver_name') is-invalid @enderror" 
                       wire:model="receiver_name"
                       id="receiver_name"
                       placeholder="Ex: Marie Martin">
                @error('receiver_name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-6 mb-3">
                @if($payment_method && in_array($payment_method, [PaymentMethod::MOBILE_MONEY()->value, PaymentMethod::ORANGE_MONEY()->value]))
                    <livewire:components.phone-input 
                        name="receiver_account"
                        label="Compte Destinataire"
                        :required="true"
                        wire:key="phone-input-receiver-{{ $payment_method }}"
                    />
                @elseif($payment_method === PaymentMethod::BANK_CARD()->value)
                    <livewire:components.card-output 
                        name="receiver_account"
                        label="Compte Destinataire"
                        :required="true"
                        :value="$receiver_account"
                        wire:key="card-output-receiver-{{ $payment_method }}"
                    />
                @elseif($payment_method)
                    <label for="receiver_account" class="form-label">Compte Destinataire <span class="text-danger">*</span></label>
                    <input type="text" 
                           class="form-control @error('receiver_account') is-invalid @enderror" 
                           wire:model="receiver_account"
                           id="receiver_account"
                           placeholder="Ex: +237680000000">
                @else
                    <label for="receiver_account" class="form-label">Compte Destinataire <span class="text-danger">*</span></label>
                    <div class="form-control-plaintext text-muted">
                        Sélectionnez d'abord une méthode de paiement
                    </div>
                @endif
                
                @error('receiver_account')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="mb-4">
            <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
            <textarea class="form-control @error('description') is-invalid @enderror" 
                      wire:model="description"
                      id="description" 
                      rows="3" 
                      placeholder="Ex: Recharge compte client via Orange Money"></textarea>
            @error('description')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="d-flex justify-content-between align-items-center">
            <button type="button" wire:click="resetForm" class="btn btn-outline-secondary">
                <i class="ti ti-refresh"></i>
                Réinitialiser
            </button>
            
            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                <span wire:loading.remove>
                    <i class="ti ti-check"></i>
                    Créer la Recharge
                </span>
                <span wire:loading>
                    <i class="ti ti-loader"></i>
                    Création en cours...
                </span>
            </button>
        </div>
    </form>
</div>