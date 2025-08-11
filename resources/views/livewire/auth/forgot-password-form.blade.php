<div>
    <div class="bg-white rounded-2xl shadow-xl p-8">
        <div class="text-center mb-8">
            <h2 class="text-3xl font-bold text-gray-900">{{ __('Forgot Password?') }}</h2>
            <p class="mt-2 text-sm text-gray-600">
                {{ __('Enter your email address and we will send you a link to reset your password.') }}
            </p>
        </div>

        @if($message)
            <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">
                {{ $message }}
            </div>
        @endif

        @if($error)
            <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
                {{ $error }}
            </div>
        @endif

        <form wire:submit.prevent="sendResetLink" class="space-y-6">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    {{ __('Reset Method') }}
                </label>
                <div class="flex items-center space-x-4">
                    <label class="inline-flex items-center">
                        <input type="radio" wire:model.live="resetMethod" value="email" class="form-radio text-blue-600" {{ $loading ? 'disabled' : '' }}>
                        <span class="ml-2 text-gray-700">{{ __('Email') }}</span>
                    </label>
                    <label class="inline-flex items-center">
                        <input type="radio" wire:model.live="resetMethod" value="phone" class="form-radio text-blue-600" {{ $loading ? 'disabled' : '' }}>
                        <span class="ml-2 text-gray-700">{{ __('Phone') }}</span>
                    </label>
                </div>
            </div>

            @if($resetMethod->equals(\App\Enums\LoginChannel::EMAIL()))
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                        {{ __('Email Address') }}
                    </label>
                    <input 
                        wire:model.live="email" 
                        type="email" 
                        id="email"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('email') border-red-500 @enderror"
                        placeholder="{{ __('your@email.com') }}"
                        {{ $loading ? 'disabled' : '' }}
                    >
                    @error('email')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            @elseif($resetMethod->equals(\App\Enums\LoginChannel::PHONE()))
                <div>
                    <livewire:components.phone-input 
                        name="phone_number" 
                        label="{{ __('Phone Number') }}" 
                        :default-country-id="$country_id"
                        required="true"
                    />
                    @error('phoneNumber')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    @error('country_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    @error('phone_number_only')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            @endif

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
                        {{ __('Sending...') }}
                    </span>
                @else
                    {{ __('Reset my password') }}
                @endif
            </button>
        </form>

        <div class="mt-6 text-center">
            <button 
                wire:click="backToLogin"
                class="text-blue-600 hover:text-blue-500 font-medium text-sm"
            >
                ← Retour à la connexion
            </button>
        </div>
    </div>
</div>
