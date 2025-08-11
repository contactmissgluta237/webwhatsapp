<div>
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('info'))
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <i class="fas fa-info-circle me-2"></i>
            {{ session('info') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($error)
        <div class="alert alert-danger" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            {{ $error }}
        </div>
    @endif

    <form wire:submit.prevent="login">
        {{-- Navigation tabs as in admin/transactions/recharge --}}
        <div class="auth-tabs">
            <div class="btn-group w-100" role="group" aria-label="{{ __('Login Method') }}">
                <input type="radio" class="btn-check" name="loginMethod" id="loginEmail" 
                       wire:model.live="loginMethod" value="email" {{ $loading ? 'disabled' : '' }}>
                <label class="btn btn-outline-primary" for="loginEmail">
                    <i class="fas fa-envelope me-2"></i>{{ __('Email') }}
                </label>
                
                <input type="radio" class="btn-check" name="loginMethod" id="loginPhone" 
                       wire:model.live="loginMethod" value="phone" {{ $loading ? 'disabled' : '' }}>
                <label class="btn btn-outline-primary" for="loginPhone">
                    <i class="fas fa-phone me-2"></i>{{ __('Phone') }}
                </label>
            </div>
        </div>

        <!-- Dynamic field based on selection -->
        @if($loginMethod === 'email')
            <div class="mb-3">
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
        @elseif($loginMethod === 'phone')
            <div class="mb-3">
                <livewire:components.phone-input 
                    name="phone_number"
                    label="{{ __('Phone Number') }}"
                    :required="true"
                    :default-country-id="1"
                    :error="$errors->first('country_id') ?: $errors->first('phone_number_only') ?: $errors->first('phone_number')"
                    wire:key="phone-input-component"
                />
                @error('country_id')
                    <div class="invalid-feedback d-block small">{{ $message }}</div>
                @enderror
                @error('phone_number_only')
                    <div class="invalid-feedback d-block small">{{ $message }}</div>
                @enderror
                @error('phone_number')
                    <div class="invalid-feedback d-block small">{{ $message }}</div>
                @enderror
            </div>
        @endif

        <!-- Password -->
        <div class="mb-3">
            <label for="password" class="form-label small fw-semibold">
                <i class="fas fa-lock me-1"></i>{{ __('Password') }}
            </label>
            <div class="input-group">
                <span class="input-group-text"><i class="fas fa-key"></i></span>
                <input type="password" id="password" wire:model="password" 
                       class="form-control @error('password') is-invalid @enderror"
                       placeholder="••••••••" required>
                <button class="btn password-toggle" type="button" onclick="togglePassword()">
                    <i class="fas fa-eye" id="toggleIcon"></i>
                </button>
            </div>
            @error('password')
                <div class="invalid-feedback d-block small">
                    <i class="fas fa-exclamation-circle me-1"></i>{{ $message }}
                </div>
            @enderror
        </div>

        <!-- Compact options -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="form-check">
                <input type="checkbox" id="remember" wire:model="remember" class="form-check-input">
                <label for="remember" class="form-check-label small">
                    <i class="fas fa-user-clock me-1"></i>{{ __('Remember me') }}
                </label>
            </div>

            <a href="{{ route('password.request') }}" class="auth-link small text-decoration-none">
                <i class="fas fa-question-circle me-1"></i>{{ __('Forgot Password?') }}
            </a>
        </div>

        <!-- Login button -->
        <button type="submit" class="btn btn-auth text-white w-100 py-2" 
                wire:loading.attr="disabled">
            <span wire:loading.remove>
                <i class="fas fa-sign-in-alt me-2"></i>{{ __('Sign in') }}
            </span>
            <span wire:loading>
                <span class="spinner-border spinner-border-sm me-2" role="status"></span>
                {{ __('Connecting...') }}
            </span>
        </button>
    </form>
</div>

<script>
    function togglePassword() {
        const passwordInput = document.getElementById('password');
        const toggleIcon = document.getElementById('toggleIcon');
        
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

    $(document).ready(function() {
        setTimeout(() => {
            if (typeof Livewire !== 'undefined') {
                Livewire.dispatch('initializePhone');
            }
        }, 250);
    });
</script>
