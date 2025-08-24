<div class="auth-card p-4">
    <div class="text-center mb-4">
        <h2 class="auth-header h3">Activation du compte</h2>
        <p class="text-muted small">
            Nous avons envoyé un code d'activation à <strong>{{ $identifier }}</strong>.<br>
            <span class="text-primary fw-medium">Vérifiez votre email et cliquez sur le lien d'activation</span><br>
            ou entrez le code ci-dessous pour activer votre compte.
        </p>
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

    <form wire:submit.prevent="activateAccount">
        <div class="mb-3">
            <label for="otpCode" class="form-label">Code d'activation</label>
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
                    Activation en cours...
                </span>
            @else
                Activer mon compte
            @endif
        </button>
    </form>

    <div class="mt-4">
        <div class="text-center mb-3">
            <p class="small text-muted mb-2">Vous n'avez pas reçu le code ?</p>
            <button 
                wire:click="resendActivationCode"
                class="btn btn-link auth-link text-decoration-none small {{ $loading ? 'disabled' : '' }}"
                {{ $loading ? 'disabled' : '' }}
            >
                Renvoyer le code d'activation
            </button>
        </div>
        
        <div class="text-center">
            <a href="{{ route('register') }}" class="btn btn-link text-muted text-decoration-none small">
                <i class="fas fa-arrow-left me-1"></i> Retour à l'inscription
            </a>
        </div>
    </div>
</div>