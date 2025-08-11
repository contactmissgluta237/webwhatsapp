<form wire:submit.prevent="submit">
    @if (session()->has('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if (session()->has('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="row">
        <div class="col-md-6">
            <div class="mb-3">
                <label for="paymentMethod" class="form-label">Type de Compte (Méthode de Paiement)</label>
                <select wire:model.live="paymentMethod" id="paymentMethod" class="form-control w-100 @error('paymentMethod') is-invalid @enderror">
                    <option value="">Sélectionnez une méthode de paiement</option>
                    @foreach ($paymentMethods as $method)
                        <option value="{{ $method->value }}">{{ $method->label }}</option>
                    @endforeach
                </select>
                @error('paymentMethod') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>
        <div class="col-md-6">
            <div class="mb-3">
                <label for="amount" class="form-label">Montant</label>
                <input type="number" wire:model="amount" id="amount" class="form-control @error('amount') is-invalid @enderror">
                @error('amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="mb-3">
                <label for="receiverName" class="form-label">Nom du Destinataire</label>
                <input type="text" wire:model="receiverName" id="receiverName" class="form-control @error('receiverName') is-invalid @enderror">
                @error('receiverName') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>
        <div class="col-md-6">
            <div class="mb-3">
                <label for="receiverAccount" class="form-label">Compte du Destinataire</label>
                <input type="text" wire:model="receiverAccount" id="receiverAccount" class="form-control @error('receiverAccount') is-invalid @enderror">
                @error('receiverAccount') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>
    </div>

    <div class="mb-3">
        <label for="description" class="form-label">Description (Optionnel)</label>
        <textarea wire:model="description" id="description" class="form-control @error('description') is-invalid @enderror"></textarea>
        @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <button type="submit" class="btn btn-info">Retirer du Compte Système</button>
</form>
