<div>
    @if($success)
        <div class="alert alert-success alert-dismissible fade show shadow-none border-success" role="alert">
            <i class="la la-check-circle me-2"></i>
            {{ $success }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if($error)
        <div class="alert alert-danger alert-dismissible fade show shadow-none border-danger" role="alert">
            <i class="la la-exclamation-circle me-2"></i>
            {{ $error }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <form wire:submit.prevent="createCoupon">
        <div class="row">
            <!-- Code coupon -->
            <div class="col-md-8 mb-3">
                <label for="code" class="form-label">Code coupon <span class="text-danger">*</span></label>
                <input type="text" 
                       class="form-control shadow-none border border-gray-light @error('code') is-invalid @enderror" 
                       wire:model="code" 
                       id="code"
                       placeholder="Ex: SAVE20, WELCOME50">
                @error('code')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-4 mb-3">
                <label class="form-label">&nbsp;</label>
                <button type="button" 
                        class="btn btn-outline-secondary w-100 shadow-none border border-gray-light" 
                        wire:click="$set('code', generateRandomCode())">
                    <i class="la la-random"></i> Générer
                </button>
            </div>

            <!-- Type -->
            <div class="col-md-6 mb-3">
                <label for="type" class="form-label">Type <span class="text-danger">*</span></label>
                <select class="form-control shadow-none border border-gray-light @error('type') is-invalid @enderror" 
                        wire:model.live="type" id="type">
                    @foreach($couponTypes as $couponType)
                        <option value="{{ $couponType['value'] }}">{{ $couponType['label'] }}</option>
                    @endforeach
                </select>
                @error('type')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <!-- Valeur -->
            <div class="col-md-6 mb-3">
                <label for="value" class="form-label">
                    Valeur <span class="text-danger">*</span>
                    @if($type === 'percentage')
                        <small class="text-muted">(0-100%)</small>
                    @else
                        <small class="text-muted">(XAF)</small>
                    @endif
                </label>
                <div class="input-group">
                    <input type="number" 
                           class="form-control shadow-none border border-gray-light @error('value') is-invalid @enderror" 
                           wire:model="value"
                           id="value"
                           step="@if($type === 'percentage') 1 @else 1 @endif"
                           min="@if($type === 'percentage') 1 @else 0.01 @endif"
                           @if($type === 'percentage') max="100" @endif
                           placeholder="@if($type === 'percentage') Ex: 20 @else Ex: 5000 @endif">
                    <span class="input-group-text border border-gray-light">
                        @if($type === 'percentage') % @else XAF @endif
                    </span>
                </div>
                @error('value')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <!-- Limite d'utilisation -->
            <div class="col-md-6 mb-3">
                <label for="usage_limit" class="form-label">Limite d'utilisation totale <span class="text-danger">*</span></label>
                <input type="number" 
                       class="form-control shadow-none border border-gray-light @error('usage_limit') is-invalid @enderror" 
                       wire:model="usage_limit" 
                       id="usage_limit"
                       min="1" 
                       max="10000"
                       placeholder="100">
                @error('usage_limit')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <!-- Limite par utilisateur -->
            <div class="col-md-6 mb-3">
                <label for="per_user_limit" class="form-label">Limite par utilisateur <span class="text-danger">*</span></label>
                <input type="number" 
                       class="form-control shadow-none border border-gray-light @error('per_user_limit') is-invalid @enderror" 
                       wire:model="per_user_limit" 
                       id="per_user_limit"
                       min="1" 
                       max="100"
                       placeholder="1">
                @error('per_user_limit')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <!-- Dates de validité -->
            <div class="col-md-6 mb-3">
                <label for="valid_from" class="form-label">Date de début (optionnel)</label>
                <input type="date" 
                       class="form-control shadow-none border border-gray-light @error('valid_from') is-invalid @enderror" 
                       wire:model="valid_from"
                       id="valid_from">
                @error('valid_from')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-6 mb-3">
                <label for="valid_until" class="form-label">Date de fin (optionnel)</label>
                <input type="date" 
                       class="form-control shadow-none border border-gray-light @error('valid_until') is-invalid @enderror" 
                       wire:model="valid_until"
                       id="valid_until">
                @error('valid_until')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <!-- Statut actif -->
            <div class="col-12 mb-4">
                <div class="form-check">
                    <input type="checkbox" 
                           class="form-check-input shadow-none border border-gray-light" 
                           wire:model="is_active" 
                           id="is_active">
                    <label class="form-check-label" for="is_active">
                        Coupon actif (peut être utilisé immédiatement)
                    </label>
                </div>
            </div>
        </div>

        <!-- Boutons d'action -->
        <div class="d-flex justify-content-between align-items-center">
            <a href="{{ route('admin.coupons.index') }}" class="btn btn-outline-secondary">
                <i class="la la-arrow-left"></i> Retour à la liste
            </a>
            
            <button type="submit" class="btn btn-whatsapp" wire:loading.attr="disabled">
                <span wire:loading.remove>
                    <i class="la la-save"></i> Créer le coupon
                </span>
                <span wire:loading>
                    <i class="la la-spinner la-spin"></i> Création en cours...
                </span>
            </button>
        </div>
    </form>
</div>