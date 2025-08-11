<div>
    <div class="bg-white rounded-2xl shadow-xl p-8">
        <div class="text-center mb-8">
            <h2 class="text-3xl font-bold text-gray-900">Activation du compte</h2>
            <p class="mt-2 text-sm text-gray-600">
                Nous avons envoyé un code d'activation à <strong>{{ $identifier }}</strong>.<br>
                <span class="text-blue-600 font-medium">Vérifiez votre email et cliquez sur le lien d'activation</span><br>
                ou entrez le code ci-dessous pour activer votre compte.
            </p>
        </div>

        @if(session('status'))
            <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">
                {{ session('status') }}
            </div>
        @endif

        @if($error)
            <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
                {{ $error }}
            </div>
        @endif

        <form wire:submit.prevent="activateAccount" class="space-y-6">
            <div>
                <label for="otpCode" class="block text-sm font-medium text-gray-700 mb-2">
                    Code d'activation
                </label>
                <input 
                    wire:model.live="otpCode" 
                    type="text" 
                    id="otpCode"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-center text-2xl tracking-widest @error('otpCode') border-red-500 @enderror"
                    placeholder="000000"
                    maxlength="6"
                    {{ $loading ? 'disabled' : '' }}
                    autofocus
                >
                @error('otpCode')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <button 
                type="submit" 
                class="w-full bg-green-600 text-white py-3 px-4 rounded-lg hover:bg-green-700 focus:ring-4 focus:ring-green-200 font-medium transition-colors duration-200 {{ $loading ? 'opacity-50 cursor-not-allowed' : '' }}"
                {{ $loading ? 'disabled' : '' }}
            >
                @if($loading)
                    <span class="flex items-center justify-center">
                        <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 714 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Activation en cours...
                    </span>
                @else
                    Activer mon compte
                @endif
            </button>
        </form>

        <div class="mt-6 space-y-4">
            <div class="text-center">
                <p class="text-sm text-gray-600 mb-2">Vous n'avez pas reçu le code ?</p>
                <button 
                    wire:click="resendActivationCode"
                    class="text-green-600 hover:text-green-500 font-medium text-sm {{ $loading ? 'opacity-50 cursor-not-allowed' : '' }}"
                    {{ $loading ? 'disabled' : '' }}
                >
                    Renvoyer le code d'activation
                </button>
            </div>
            
            <div class="text-center">
                <a href="{{ route('register') }}" class="text-gray-500 hover:text-gray-700 font-medium text-sm">
                    ← Retour à l'inscription
                </a>
            </div>
        </div>
    </div>
</div>