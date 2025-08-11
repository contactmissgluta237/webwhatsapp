@php
use App\Enums\TransactionMode;
@endphp

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
            <label for="amount" class="form-label">Montant à recharger <span class="text-danger">*</span></label>
            <div class="input-group">
                <span class="input-group-text">FCFA</span>
                <select class="form-control @error('amount') is-invalid @enderror" 
                        wire:model.live="amount" id="amount">
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
                    Frais de recharge : <strong>{{ number_format($feeAmount, 2, ',', ' ') }} FCFA</strong>. Le client paiera au total : <strong>{{ number_format($totalToPay, 2, ',', ' ') }} FCFA</strong>.
                </div>
            @endif

            @error('amount')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    @if ($customer_id && $customer_wallet_balance !== null)
        <div class="alert alert-success mt-3 text-center fs-5">
            Solde actuel du portefeuille du client: <strong class="fs-4">{{ number_format($customer_wallet_balance, 0, ',', ' ') }} FCFA</strong>
        </div>
    @endif

    <div class="mb-1">
        <label class="form-label">Mode de Recharge <span class="text-danger">*</span></label>
        <div class="row">
            @foreach($rechargeModes as $mode)
                <div class="col-md-6 mb-1 mb-md-0">
                    <div class="d-flex justify-content-between">
                        <input type="radio" class="btn-check mr-1" name="recharge_mode" id="recharge_mode_{{ $mode['value'] }}"
                               autocomplete="off" wire:model.live="recharge_mode" value="{{ $mode['value'] }}">
                        <label class="btn btn-outline-info w-100" for="recharge_mode_{{ $mode['value'] }}">
                            {{ $mode['label'] }}
                        </label>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    @if($customer_id && $amount)
        @if($recharge_mode === TransactionMode::MANUAL()->value)
            <livewire:admin.recharge.manual-recharge-form :customer_id="$customer_id" :amount="$amount" />
        @elseif($recharge_mode === TransactionMode::AUTOMATIC()->value)
            <livewire:admin.recharge.automatic-recharge-form :customer_id="$customer_id" :amount="$amount" />
        @endif
    @else
        <div class="alert alert-info mt-3" role="alert">
            Veuillez sélectionner un client et un montant pour continuer.
        </div>
    @endif

    @if($success)
        <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            {{ $success }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <div class="d-flex justify-content-end mt-3">
            <button type="button" wire:click="resetForm" class="btn btn-outline-secondary">
                <i class="ti ti-refresh"></i>
                Nouvelle Recharge
            </button>
        </div>
    @endif