<div class="auth-card p-4">
    <div class="text-center mb-4">
        <h2 class="auth-header h3">{{ __('Forgot Password?') }}</h2>
        <p class="text-muted small">
            {{ __('Enter your email address and we will send you a link to reset your password.') }}
        </p>
    </div>

    @if($message)
        <div class="alert alert-success mb-3">
            {{ $message }}
        </div>
    @endif

    @if($error)
        <div class="alert alert-danger mb-3">
            {{ $error }}
        </div>
    @endif

    <form wire:submit.prevent="sendResetLink">
        <div class="mb-3">
            <label class="form-label">{{ __('Reset Method') }}</label>
            <div class="auth-tabs btn-group w-100" role="group">
                <input type="radio" wire:model.live="resetMethod" value="email" class="btn-check" id="email-method" {{ $loading ? 'disabled' : '' }}>
                <label class="btn btn-outline-primary" for="email-method">
                    <i class="fas fa-envelope me-2"></i>{{ __('Email') }}
                </label>
                
                <input type="radio" wire:model.live="resetMethod" value="phone" class="btn-check" id="phone-method" {{ $loading ? 'disabled' : '' }}>
                <label class="btn btn-outline-primary" for="phone-method">
                    <i class="fas fa-phone me-2"></i>{{ __('Phone') }}
                </label>
            </div>
        </div>

        @if($resetMethod === 'email')
            <div class="mb-3">
                <label for="email" class="form-label small fw-semibold">
                    <i class="fas fa-envelope me-1"></i>{{ __('Email Address') }}
                </label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-at"></i></span>
                    <input type="email" id="email" wire:model.live="email" 
                           class="form-control @error('email') is-invalid @enderror"
                           placeholder="{{ __('your@email.com') }}" 
                           {{ $loading ? 'disabled' : '' }}>
                </div>
                @error('email')
                    <div class="invalid-feedback d-block small">
                        <i class="fas fa-exclamation-circle me-1"></i>{{ $message }}
                    </div>
                @enderror
            </div>
        @elseif($resetMethod === 'phone')
            <div class="mb-3">
                <livewire:components.phone-input 
                    name="phone_number" 
                    label="{{ __('Phone Number') }}" 
                    :default-country-id="$country_id"
                    required="true"
                />
                @error('phoneNumber')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
                @error('country_id')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
                @error('phone_number_only')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>
        @endif

        <button 
            type="submit" 
            class="btn btn-auth text-white w-100 {{ $loading ? 'disabled' : '' }}"
            {{ $loading ? 'disabled' : '' }}
        >
            @if($loading)
                <span class="d-flex align-items-center justify-content-center">
                    <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                    {{ __('Sending...') }}
                </span>
            @else
                {{ __('Reset my password') }}
            @endif
        </button>
    </form>

    <div class="text-center mt-3">
        <button 
            wire:click="backToLogin"
            class="btn btn-link auth-link text-decoration-none"
        >
            <i class="fas fa-arrow-left me-1"></i> Retour à la connexion
        </button>
    </div>
</div>
