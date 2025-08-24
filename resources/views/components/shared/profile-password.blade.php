@props(['current_password', 'password', 'password_confirmation'])

<div class="card shadow-none border-gray-light">
    <div class="card-body">
        <h5 class="card-title d-flex align-items-center gap-2 mb-4">
            <i class="ti ti-lock"></i>
            Changer le mot de passe
        </h5>
        
        <form wire:submit.prevent="updatePassword">
            <div class="mb-3">
                <label for="current_password" class="form-label">Mot de passe actuel</label>
                <input type="password" class="form-control @error('current_password') is-invalid @enderror" 
                       id="current_password" wire:model="current_password">
                @error('current_password')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="password" class="form-label">Nouveau mot de passe</label>
                        <input type="password" class="form-control @error('password') is-invalid @enderror" 
                               id="password" wire:model="password">
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="password_confirmation" class="form-label">Confirmer le mot de passe</label>
                        <input type="password" class="form-control" 
                               id="password_confirmation" wire:model="password_confirmation">
                    </div>
                </div>
            </div>
            
            <button type="submit" class="btn btn-whatsapp w-100" wire:loading.attr="disabled">
                <span wire:loading.remove">Changer le mot de passe</span>
                <span wire:loading>{{ __('profile.changing') }}</span>
            </button>
        </form>
    </div>
</div>