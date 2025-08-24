<div class="auth-card p-4">
    <div class="text-center mb-4">
        <h2 class="auth-header h3">Nouveau mot de passe</h2>
        <p class="text-muted small">
            Choisissez un nouveau mot de passe sécurisé pour votre compte.
        </p>
    </div>

    @if($error)
        <div class="alert alert-danger mb-3">
            {{ $error }}
        </div>
    @endif

    <form wire:submit.prevent="resetPassword">
        <div class="mb-3">
            <label for="identifier" class="form-label">{{ $this->getIdentifierLabel() }}</label>
            <input
                wire:model="identifier"
                type="{{ $this->getIdentifierInputType() }}"
                id="identifier"
                class="form-control bg-light @error('identifier') is-invalid @enderror"
                readonly
            >
            @error('identifier')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <!-- Le champ token est maintenant caché car il a déjà été vérifié -->
        <input type="hidden" wire:model="token">

        <div class="mb-3">
            <label for="password" class="form-label small fw-semibold">
                <i class="fas fa-lock me-1"></i>{{ __('New Password') }}
            </label>
            <div class="input-group">
                <span class="input-group-text"><i class="fas fa-key"></i></span>
                <input type="password" id="password" wire:model.live="password" 
                       class="form-control @error('password') is-invalid @enderror"
                       placeholder="••••••••" {{ $loading ? 'disabled' : '' }}>
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

        <div class="mb-3">
            <label for="password_confirmation" class="form-label small fw-semibold">
                <i class="fas fa-lock me-1"></i>{{ __('Confirm Password') }}
            </label>
            <div class="input-group">
                <span class="input-group-text"><i class="fas fa-key"></i></span>
                <input type="password" id="password_confirmation" wire:model.live="password_confirmation" 
                       class="form-control" placeholder="••••••••" {{ $loading ? 'disabled' : '' }}>
                <button class="btn password-toggle" type="button" onclick="togglePasswordField('password_confirmation', 'toggleIcon2')">
                    <i class="fas fa-eye" id="toggleIcon2"></i>
                </button>
            </div>
        </div>

        <button 
            type="submit" 
            class="btn btn-auth text-white w-100 {{ $loading ? 'disabled' : '' }}"
            {{ $loading ? 'disabled' : '' }}
        >
            @if($loading)
                <span class="d-flex align-items-center justify-content-center">
                    <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                    Réinitialisation...
                </span>
            @else
                Réinitialiser le mot de passe
            @endif
        </button>
    </form>

    <div class="text-center mt-3">
        <a href="{{ route('login') }}" class="btn btn-link auth-link text-decoration-none">
            <i class="fas fa-arrow-left me-1"></i> {{ __('Back to login') }}
        </a>
    </div>
</div>

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
</script>
