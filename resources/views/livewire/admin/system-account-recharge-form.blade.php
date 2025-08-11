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
                <select wire:model.live="paymentMethod" id="paymentMethod" class="form-control @error('paymentMethod') is-invalid @enderror">
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
                <label for="senderName" class="form-label">Nom de l'Expéditeur</label>
                <input type="text" wire:model="senderName" id="senderName" class="form-control @error('senderName') is-invalid @enderror">
                @error('senderName') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>
        <div class="col-md-6">
            <div class="mb-3">
                <label for="senderAccount" class="form-label">Compte de l'Expéditeur</label>
                <input type="text" wire:model="senderAccount" id="senderAccount" class="form-control @error('senderAccount') is-invalid @enderror">
                @error('senderAccount') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>
    </div>

    <div class="mb-3">
        <label for="description" class="form-label">Description (Optionnel)</label>
        <textarea wire:model="description" id="description" class="form-control @error('description') is-invalid @enderror"></textarea>
        @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <button type="submit" class="btn btn-info">Recharger le Compte Système</button>
</form>
