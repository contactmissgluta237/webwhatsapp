@php
use App\Enums\PaymentMethod;
@endphp

<form wire:submit.prevent="submitWithdrawal">
    <div class="row">
        <div class="col-md-6 mb-1">
            <label for="customer_id" class="form-label">Client <span class="text-danger">*</span></label>
            <select class="form-control @error('customer_id') is-invalid @enderror" 
                    wire:model.live="customer_id" id="customer_id">
                <option value="">-- Sélectionnez un client --</option>
                @foreach($customers as $customer)
                    <option value="{{ $customer->id }}">
                        {{ $customer->first_name }} {{ $customer->last_name }} ({{ $customer->email }})
                    </option>
                @endforeach
            </select>
            @error('customer_id')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-md-6 mb-1">
            <label for="amount" class="form-label">Montant (FCFA) <span class="text-danger">*</span></label>
            <select class="form-control @error('amount') is-invalid @enderror" 
                    wire:model.live="amount" id="amount">
                <option value="">-- Sélectionnez un montant --</option>
                @foreach($predefinedAmounts as $amountOption)
                    <option value="{{ $amountOption }}">
                        {{ number_format($amountOption, 0, ',', ' ') }} FCFA
                    </option>
                @endforeach
            </select>
            @error('amount')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
            @if ($feeAmount !== null && $finalAmount !== null)
                <div class="mt-2 text-info">
                    Frais de retrait : <strong>{{ number_format($feeAmount, 2, ',', ' ') }} FCFA</strong>. Le client recevra : <strong>{{ number_format($finalAmount, 2, ',', ' ') }} FCFA</strong>.
                </div>
            @endif

        </div>
    </div>

    @if ($customer_id && $customer_wallet_balance !== null)
        <div class="alert alert-info mt-3">
            Solde actuel du portefeuille du client: <strong>{{ number_format($customer_wallet_balance, 0, ',', ' ') }} FCFA</strong>
        </div>
    @endif

    <div class="row">
        <div class="col-md-6 mb-1">
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

        <div class="col-md-6 mb-1">
            <label for="payment_method" class="form-label">Méthode de Paiement <span class="text-danger">*</span></label>
            <select class="form-control @error('payment_method') is-invalid @enderror" 
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
        <div class="col-md-6 mb-1">
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

        <div class="col-md-6 mb-1">
            @include('livewire.admin.withdrawal.partials.account-input', [
                'name' => 'sender_account',
                'label' => 'Compte Expéditeur',
                'value' => $sender_account
            ])
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 mb-1">
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

        <div class="col-md-6 mb-1">
            @include('livewire.admin.withdrawal.partials.account-input', [
                'name' => 'receiver_account',
                'label' => 'Compte Destinataire',
                'value' => $receiver_account
            ])
        </div>
    </div>

    <div class="mb-4">
        <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
        <textarea class="form-control @error('description') is-invalid @enderror" 
                  wire:model="description"
                  id="description" 
                  rows="3" 
                  placeholder="Ex: Retrait compte client via Orange Money"></textarea>
        @error('description')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    @include('livewire.admin.withdrawal.partials.form-actions')
</form>
