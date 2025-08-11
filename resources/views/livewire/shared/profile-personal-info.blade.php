<!-- Informations personnelles -->
<div class="card mb-4">
    <div class="card-body">
        <h5 class="card-title d-flex align-items-center gap-2 mb-4">
            <i class="ti ti-user-circle"></i>
            Informations personnelles
        </h5>
        
        <form wire:submit.prevent="updateProfile">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="first_name" class="form-label">Prénom</label>
                        <input type="text" class="form-control @error('first_name') is-invalid @enderror" 
                               id="first_name" wire:model="first_name">
                        @error('first_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="last_name" class="form-label">Nom de famille</label>
                        <input type="text" class="form-control @error('last_name') is-invalid @enderror" 
                               id="last_name" wire:model="last_name">
                        @error('last_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        @if($this->isCustomer())
                            <input type="email" class="form-control bg-light" 
                                   id="email" value="{{ $email }}" readonly>
                            <div class="form-text">
                                <i class="ti ti-info-circle"></i>
                                Contactez un administrateur pour modifier votre email
                            </div>
                        @else
                            <input type="email" class="form-control @error('email') is-invalid @enderror" 
                                   id="email" wire:model="email">
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        @endif
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="phone_number" class="form-label">Téléphone</label>
                        @if($this->isCustomer())
                            <input type="text" class="form-control bg-light" 
                                   id="phone_number" value="{{ $phone_number }}" readonly>
                            <div class="form-text">
                                <i class="ti ti-info-circle"></i>
                                Contactez un administrateur pour modifier votre téléphone
                            </div>
                        @else
                            <input type="text" class="form-control @error('phone_number') is-invalid @enderror" 
                                   id="phone_number" wire:model="phone_number">
                            @error('phone_number')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        @endif
                    </div>
                </div>
            </div>
            
            <button type="submit" class="btn btn-success" wire:loading.attr="disabled">
                <span wire:loading.remove>Mettre à jour le profil</span>
                <span wire:loading>Mise à jour...</span>
            </button>
        </form>
    </div>
</div>