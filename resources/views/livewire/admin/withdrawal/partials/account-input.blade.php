@php
use App\Enums\PaymentMethod;
@endphp

@if($payment_method && in_array($payment_method, [PaymentMethod::MOBILE_MONEY()->value, PaymentMethod::ORANGE_MONEY()->value]))
    <livewire:components.phone-input 
        :name="$name"
        :label="$label"
        :required="true"
        wire:key="phone-input-{{ $name }}-{{ $payment_method }}"
    />
@elseif($payment_method === PaymentMethod::BANK_CARD()->value)
    <livewire:components.card-output 
        :name="$name"
        :label="$label"
        :required="true"
        :value="$value"
        wire:key="card-output-{{ $name }}-{{ $payment_method }}"
    />
@elseif($payment_method)
    <label for="{{ $name }}" class="form-label">{{ $label }} <span class="text-danger">*</span></label>
    <input type="text" 
           class="form-control @error($name) is-invalid @enderror" 
           wire:model="{{ $name }}"
           id="{{ $name }}"
           placeholder="Ex: +237680000000">
    @error($name)
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
@else
    <label for="{{ $name }}" class="form-label">{{ $label }} <span class="text-danger">*</span></label>
    <div class="form-control-plaintext text-muted">
        Sélectionnez d'abord une méthode de paiement
    </div>
@endif