<div>
    <div class="bg-white rounded-2xl shadow-xl p-8">
        <div class="text-center mb-8">
            <h2 class="text-3xl font-bold text-gray-900">Nouveau mot de passe</h2>
            <p class="mt-2 text-sm text-gray-600">
                Choisissez un nouveau mot de passe sécurisé pour votre compte.
            </p>
        </div>

        @if($error)
            <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
                {{ $error }}
            </div>
        @endif

        <form wire:submit.prevent="resetPassword" class="space-y-6">
            <div>
                <label for="identifier" class="block text-sm font-medium text-gray-700 mb-2">
                    {{ $this->getIdentifierLabel() }}
                </label>
                <input
                    wire:model="identifier"
                    type="{{ $this->getIdentifierInputType() }}"
                    id="identifier"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg bg-gray-50 @error('identifier') border-red-500 @enderror"
                    readonly
                >
                @error('identifier')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Le champ token est maintenant caché car il a déjà été vérifié -->
            <input type="hidden" wire:model="token">

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                    Nouveau mot de passe
                </label>
                <input 
                    wire:model.live="password" 
                    type="password" 
                    id="password"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('password') border-red-500 @enderror"
                    placeholder="••••••••"
                    {{ $loading ? 'disabled' : '' }}
                >
                @error('password')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-2">
                    Confirmer le mot de passe
                </label>
                <input 
                    wire:model.live="password_confirmation" 
                    type="password" 
                    id="password_confirmation"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="••••••••"
                    {{ $loading ? 'disabled' : '' }}
                >
            </div>

            <button 
                type="submit" 
                class="w-full bg-blue-600 text-white py-3 px-4 rounded-lg hover:bg-blue-700 focus:ring-4 focus:ring-blue-200 font-medium transition-colors duration-200 {{ $loading ? 'opacity-50 cursor-not-allowed' : '' }}"
                {{ $loading ? 'disabled' : '' }}
            >
                @if($loading)
                    <span class="flex items-center justify-center">
                        <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Réinitialisation...
                    </span>
                @else
                    Réinitialiser le mot de passe
                @endif
            </button>
        </form>

        <div class="mt-6 text-center">
            <a href="{{ route('login') }}" class="text-blue-600 hover:text-blue-500 font-medium text-sm">
                ← Retour à la connexion
            </a>
        </div>
    </div>
</div>
