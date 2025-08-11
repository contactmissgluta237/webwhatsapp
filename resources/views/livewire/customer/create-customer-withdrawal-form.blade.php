@php
    use App\Enums\PaymentMethod;
@endphp
<div>
    <form wire:submit.prevent="createWithdrawal">
        @csrf

        @if ($success)
            <div class="alert alert-success">
                {{ $success }}
            </div>
        @endif

        @if ($error)
            <div class="alert alert-danger">
                {{ $error }}
            </div>
        @endif

        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">Montant à retirer</label>
                    <div class="input-group">
                        <span class="input-group-text">FCFA</span>
                        <select class="form-select" wire:model.live="amount">
                            <option value="">Sélectionner un montant</option>
                            @foreach ($predefinedAmounts as $pAmount)
                                <option value="{{ $pAmount }}">{{ number_format($pAmount, 0, ',', ' ') }}</option>
                            @endforeach
                                                </select>
                    </div>
                    @if ($feeAmount !== null && $finalAmount !== null)
                        <div class="mt-2 text-info">
                            Frais de retrait : <strong>{{ number_format($feeAmount, 2, ',', ' ') }} FCFA</strong>. Vous recevrez : <strong>{{ number_format($finalAmount, 2, ',', ' ') }} FCFA</strong>.
                        </div>
                    @endif

                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">Méthode de paiement</label>
                    <select class="form-select" wire:model.live="payment_method">
                        <option value="">Sélectionner une méthode</option>
                        @foreach ($paymentMethods as $method)
                            <option value="{{ $method['value'] }}">{{ $method['label'] }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        @if (in_array($payment_method, [PaymentMethod::MOBILE_MONEY()->value, PaymentMethod::ORANGE_MONEY()->value]))
            <div class="row">
                <div class="col-md-12">
                    <div class="mb-3">
                        <label class="form-label">Numéro de téléphone</label>
                        <livewire:components.phone-input 
                            name="receiver_account" 
                            :value="$phone_number"
                        />
                    </div>
                </div>
            </div>
        @endif

        @if ($payment_method === PaymentMethod::BANK_CARD()->value)
            <div class="row">
                <div class="col-md-12">
                    <div class="mb-3">
                        <livewire:components.card-output 
                            name="receiver_account" 
                            :value="$card_number"
                        />
                    </div>
                </div>
            </div>
        @endif

        <div class="d-flex justify-content-end mt-4">
            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                <span wire:loading.remove>Initier le retrait</span>
                <span wire:loading>Chargement...</span>
            </button>
        </div>
    </form>
</div>
