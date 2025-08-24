<form wire:submit.prevent="register">
    @if($error)
        <div class="alert alert-danger mb-3">
            {{ $error }}
        </div>
    @endif

    <div class="row mb-3">
        <div class="col-md-6">
            <label for="first_name" class="form-label">{{ __('First Name') }}</label>
            <input 
                type="text" 
                id="first_name"
                wire:model="first_name" 
                class="form-control @error('first_name') is-invalid @enderror"
                placeholder="{{ __('John') }}"
                required
            >
            @error('first_name')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-md-6">
            <label for="last_name" class="form-label">{{ __('Last Name') }}</label>
            <input 
                type="text" 
                id="last_name"
                wire:model="last_name" 
                class="form-control @error('last_name') is-invalid @enderror"
                placeholder="{{ __('Doe') }}"
                required
            >
            @error('last_name')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-6">
            <label for="email" class="form-label small fw-semibold">
                <i class="fas fa-envelope me-1"></i>{{ __('Email Address') }}
            </label>
            <div class="input-group">
                <span class="input-group-text"><i class="fas fa-at"></i></span>
                <input type="email" id="email" wire:model="email" 
                       class="form-control @error('email') is-invalid @enderror"
                       placeholder="{{ __('your@email.com') }}" required>
            </div>
            @error('email')
                <div class="invalid-feedback d-block small">
                    <i class="fas fa-exclamation-circle me-1"></i>{{ $message }}
                </div>
            @enderror
        </div>

        <div class="col-md-6">
            <livewire:components.phone-input 
                name="phone_number"
                label="{{ __('Phone Number') }} ({{ __('optional') }})"
                :required="false"
                :default-country-id="1"
                :error="$errors->first('country_id') ?: $errors->first('phone_number_only') ?: $errors->first('phone_number')"
                wire:key="phone-input-component"
            />
            @error('country_id')
                <div class="text-danger small mt-1">{{ $message }}</div>
            @enderror
            @error('phone_number_only')
                <div class="text-danger small mt-1">{{ $message }}</div>
            @enderror
            @error('phone_number')
                <div class="text-danger small mt-1">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-6">
            <label for="password" class="form-label small fw-semibold">
                <i class="fas fa-lock me-1"></i>{{ __('Password') }}
            </label>
            <div class="input-group">
                <span class="input-group-text"><i class="fas fa-key"></i></span>
                <input type="password" id="password" wire:model="password" 
                       class="form-control @error('password') is-invalid @enderror"
                       placeholder="••••••••" required>
                <button class="btn password-toggle" type="button" onclick="togglePasswordField('password', 'toggleIcon1')">
                    <i class="fas fa-eye" id="toggleIcon1"></i>
                </button>
            </div>
            @error('password')
                <div class="invalid-feedback d-block small">
                    <i class="fas fa-exclamation-circle me-1"></i>{{ $message }}
                </div>
            @enderror
        </div>

        <div class="col-md-6">
            <label for="password_confirmation" class="form-label small fw-semibold">
                <i class="fas fa-lock me-1"></i>{{ __('Confirm Password') }}
            </label>
            <div class="input-group">
                <span class="input-group-text"><i class="fas fa-key"></i></span>
                <input type="password" id="password_confirmation" wire:model="password_confirmation" 
                       class="form-control" placeholder="••••••••" required>
                <button class="btn password-toggle" type="button" onclick="togglePasswordField('password_confirmation', 'toggleIcon2')">
                    <i class="fas fa-eye" id="toggleIcon2"></i>
                </button>
            </div>
        </div>
    </div>

    <div class="row mb-3">
        {{-- Code de parrainage --}}
        <div class="col-md-6">
            <label for="referral_code" class="form-label">{{ __('Referral Code') }} ({{ __('optional') }})</label>
            <input 
                type="text" 
                id="referral_code"
                wire:model="referral_code" 
                class="form-control @error('referral_code') is-invalid @enderror @if($referral_code_readonly) bg-light @endif"
                placeholder="{{ __('Enter referral code') }}"
                @if($referral_code_readonly) readonly @endif
            >
            @if($referral_code_readonly)
                <div class="form-text text-primary">{{ __('Referral code pre-filled from link') }}</div>
            @endif
            @error('referral_code')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        {{-- Sélection de la langue --}}
        <div class="col-md-6">
            <label for="locale" class="form-label small fw-semibold">
                <i class="fas fa-globe me-1"></i>{{ __('Preferred Language') }}
            </label>
            <select class="form-select @error('locale') is-invalid @enderror" id="locale" wire:model="locale">
                <option value="en">🇺🇸 {{ __('English') }}</option>
                <option value="fr">🇫🇷 {{ __('Français') }}</option>
            </select>
            @error('locale')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="mb-3 form-check">
        <input 
            type="checkbox" 
            id="terms" 
            wire:model="terms"
            class="form-check-input @error('terms') is-invalid @enderror"
            required
        >
        <label for="terms" class="form-check-label">
            {{ __('I agree to the') }} <a href="#" class="auth-link">{{ __('terms of service') }}</a>
        </label>
        @error('terms')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <button 
        type="submit" 
        class="btn btn-auth text-white w-100 {{ $loading ? 'disabled' : '' }}"
        {{ $loading ? 'disabled' : '' }}
    >
        @if($loading)
            <span class="d-flex align-items-center justify-content-center">
                <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                {{ __('Creating...') }}
            </span>
        @else
            {{ __('Create Account') }}
        @endif
    </button>
</form>

{{-- ✅ SCRIPT D'INITIALISATION --}}
<script>
// Toggle password visibility
function togglePasswordField(fieldId, iconId) {
    const passwordInput = document.getElementById(fieldId);
    const toggleIcon = document.getElementById(iconId);
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleIcon.classList.remove('fa-eye');
        toggleIcon.classList.add('fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        toggleIcon.classList.remove('fa-eye-slash');
        toggleIcon.classList.add('fa-eye');
    }
}

document.addEventListener('livewire:initialized', function () {
    console.log('Livewire initialized, initializing phone component');
    
    // Attendre un court délai pour que tous les composants soient montés
    setTimeout(() => {
        // Déclencher l'initialisation du composant phone
        Livewire.dispatch('initializePhone');
        console.log('Phone initialization dispatched');
    }, 250);
});
</script>
