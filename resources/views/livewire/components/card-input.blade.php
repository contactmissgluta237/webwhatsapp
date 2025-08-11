<div class="card-input-component">
    <label class="form-label">
        {{ $label }}
        @if($required)
            <span class="text-danger">*</span>
        @endif
    </label>

    <div class="row">
        <!-- Numéro de carte -->
        <div class="col-12 mb-3">
            <div class="form-group">
                <label class="form-label">Numéro de carte</label>
                <input 
                    type="text" 
                    class="form-control {{ isset($validationErrors['cardNumber']) ? 'is-invalid' : '' }}"
                    wire:model.live="cardNumber"
                    placeholder="1234 5678 9012 3456"
                    maxlength="19"
                    autocomplete="cc-number"
                >
                @if(isset($validationErrors['cardNumber']))
                    <div class="invalid-feedback">{{ $validationErrors['cardNumber'] }}</div>
                @endif
            </div>
        </div>

        <!-- CVV et Date d'expiration -->
        <div class="col-md-4 mb-3">
            <div class="form-group">
                <label class="form-label">CVV</label>
                <input 
                    type="text" 
                    class="form-control {{ isset($validationErrors['cvv']) ? 'is-invalid' : '' }}"
                    wire:model.live="cvv"
                    placeholder="123"
                    maxlength="3"
                    autocomplete="cc-csc"
                >
                @if(isset($validationErrors['cvv']))
                    <div class="invalid-feedback">{{ $validationErrors['cvv'] }}</div>
                @endif
            </div>
        </div>

        <div class="col-md-4 mb-3">
            <div class="form-group">
                <label class="form-label">Mois</label>
                <input 
                    type="text" 
                    class="form-control {{ isset($validationErrors['expiryMonth']) ? 'is-invalid' : '' }}"
                    wire:model.live="expiryMonth"
                    placeholder="MM"
                    maxlength="2"
                    autocomplete="cc-exp-month"
                >
                @if(isset($validationErrors['expiryMonth']))
                    <div class="invalid-feedback">{{ $validationErrors['expiryMonth'] }}</div>
                @endif
            </div>
        </div>

        <div class="col-md-4 mb-3">
            <div class="form-group">
                <label class="form-label">Année</label>
                <input 
                    type="text" 
                    class="form-control {{ isset($validationErrors['expiryYear']) ? 'is-invalid' : '' }}"
                    wire:model.live="expiryYear"
                    placeholder="YY"
                    maxlength="2"
                    autocomplete="cc-exp-year"
                >
                @if(isset($validationErrors['expiryYear']))
                    <div class="invalid-feedback">{{ $validationErrors['expiryYear'] }}</div>
                @endif
            </div>
        </div>
    </div>

    @if(isset($validationErrors['expiry']))
        <div class="text-danger small mb-2">{{ $validationErrors['expiry'] }}</div>
    @endif

    @if($error)
        <div class="text-danger small">{{ $error }}</div>
    @endif

    <!-- Aperçu du numéro masqué -->
    @if($maskedCardNumber)
        <div class="mt-2">
            <small class="text-muted">Numéro masqué: <strong>{{ $maskedCardNumber }}</strong></small>
        </div>
    @endif
</div>