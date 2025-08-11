<form wire:submit.prevent="register" class="space-y-4">
    @if($error)
        <div class="alert bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
            {{ $error }}
        </div>
    @endif

    <div class="flex space-x-4">
        <div class="w-1/2">
            <label for="first_name" class="block text-sm font-medium text-gray-700 mb-2">
                Prénom
            </label>
            <input 
                type="text" 
                id="first_name"
                wire:model="first_name" 
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('first_name') border-red-500 @enderror"
                placeholder="John"
                required
            >
            @error('first_name')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="w-1/2">
            <label for="last_name" class="block text-sm font-medium text-gray-700 mb-2">
                Nom de famille
            </label>
            <input 
                type="text" 
                id="last_name"
                wire:model="last_name" 
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('last_name') border-red-500 @enderror"
                placeholder="Doe"
                required
            >
            @error('last_name')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div class="flex space-x-4">
        <div class="w-1/2">
            <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                Email
            </label>
            <input 
                type="email" 
                id="email"
                wire:model="email" 
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('email') border-red-500 @enderror"
                placeholder="john@example.com"
                required
            >
            @error('email')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="w-1/2">
            <livewire:components.phone-input 
                name="phone_number"
                label="Numéro de téléphone (optionnel)"
                :required="false"
                :default-country-id="1"
                :error="$errors->first('country_id') ?: $errors->first('phone_number_only') ?: $errors->first('phone_number')"
                wire:key="phone-input-component"
            />
            @error('country_id')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
            @error('phone_number_only')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
            @error('phone_number')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div class="flex space-x-4">
        <div class="w-1/2">
            <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                Mot de passe
            </label>
            <input 
                type="password" 
                id="password"
                wire:model="password" 
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('password') border-red-500 @enderror"
                placeholder="••••••••"
                required
            >
            @error('password')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="w-1/2">
            <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-2">
                Confirmer le mot de passe
            </label>
            <input 
                type="password" 
                id="password_confirmation"
                wire:model="password_confirmation" 
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="••••••••"
                required
            >
        </div>
    </div>

    {{-- Code de parrainage --}}
    <div>
        <label for="referral_code" class="block text-sm font-medium text-gray-700 mb-2">
            Code de parrainage (optionnel)
        </label>
        <input 
            type="text" 
            id="referral_code"
            wire:model="referral_code" 
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('referral_code') border-red-500 @enderror @if($referral_code_readonly) bg-gray-100 @endif"
            placeholder="Entrez le code de parrainage"
            @if($referral_code_readonly) readonly @endif
        >
        @if($referral_code_readonly)
            <p class="mt-1 text-sm text-blue-600">Code de parrainage pré-renseigné depuis le lien</p>
        @endif
        @error('referral_code')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="flex items-center">
        <input 
            type="checkbox" 
            id="terms" 
            wire:model="terms"
            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded @error('terms') border-red-500 @enderror"
            required
        >
        <label for="terms" class="ml-2 block text-sm text-gray-700">
            J'accepte les <a href="#" class="text-blue-600 hover:text-blue-800">conditions d'utilisation</a>
        </label>
    </div>
    @error('terms')
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror

    <button 
        type="submit" 
        class="w-full flex justify-center py-2 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
        wire:loading.attr="disabled"
    >
        <span wire:loading.remove>Créer mon compte</span>
        <span wire:loading class="flex items-center">
            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Création...
        </span>
    </button>
</form>

{{-- ✅ SCRIPT D'INITIALISATION --}}
<script>
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
