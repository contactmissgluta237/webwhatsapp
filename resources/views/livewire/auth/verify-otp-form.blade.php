<div class="auth-card p-4">
    <div class="text-center mb-4">
        <h2 class="auth-header h3">
            @if($verificationType === 'register')
                Activation du compte
            @else
                Réinitialisation du mot de passe
            @endif
        </h2>
        
        @if($verificationType === 'register')
            <p class="text-muted small">
                Nous avons envoyé un code d'activation à <strong>{{ $identifier }}</strong>.<br>
                <span class="text-primary fw-medium">Vérifiez votre email et entrez le code ci-dessous pour activer votre compte.</span>
            </p>
        @else
            @if($resetType === 'email')
                <p class="text-muted small">
                    Nous avons envoyé un code de vérification à <strong>{{ $identifier }}</strong>.<br>
                    <span class="text-primary fw-medium">Vérifiez votre email et cliquez sur le lien de réinitialisation</span><br>
                    ou entrez le code ci-dessous pour continuer.
                </p>
            @else
                <p class="text-muted small">
                    Nous avons envoyé un code de vérification par SMS au <strong>{{ $identifier }}</strong>.<br>
                    Entrez le code reçu pour continuer.
                </p>
            @endif
        @endif
    </div>

    @if(session('status'))
        <div class="alert alert-success mb-3">
            {{ session('status') }}
        </div>
    @endif

    @if($error)
        <div class="alert alert-danger mb-3">
            {{ $error }}
        </div>
    @endif

    <form wire:submit.prevent="verifyOtp">
        <div class="mb-3">
            <label for="otpCode" class="form-label">
                @if($verificationType === 'register')
                    Code d'activation
                @else
                    Code de vérification
                @endif
            </label>
            <input
                wire:model.live="otpCode"
                type="text"
                id="otpCode"
                class="form-control text-center fs-4 @error('otpCode') is-invalid @enderror"
                style="letter-spacing: 0.5rem;"
                placeholder="000000"
                maxlength="6"
                {{ $loading ? 'disabled' : '' }}
                autofocus
            >
            @error('otpCode')
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
                    @if($verificationType === 'register')
                        Activation...
                    @else
                        Vérification...
                    @endif
                </span>
            @else
                @if($verificationType === 'register')
                    Activer mon compte
                @else
                    Vérifier le code
                @endif
            @endif
        </button>
    </form>

    <div class="mt-4">
        <div class="text-center mb-3">
            <p class="small text-muted mb-2">Vous n'avez pas reçu le code ?</p>
            <button
                wire:click="resendOtp"
                class="btn btn-link auth-link text-decoration-none small {{ $loading ? 'disabled' : '' }}"
                {{ $loading ? 'disabled' : '' }}
            >
                Renvoyer le code
            </button>
        </div>
        
        <div class="text-center">
            @if($verificationType === 'register')
                <a href="{{ route('register') }}" class="btn btn-link text-muted text-decoration-none small">
                    <i class="fas fa-arrow-left me-1"></i> Retour à l'inscription
                </a>
            @else
                <button
                    wire:click="backToForgotPassword"
                    class="btn btn-link text-muted text-decoration-none small"
                >
                    <i class="fas fa-arrow-left me-1"></i> Retour
                </button>
            @endif
        </div>
    </div>
</div>