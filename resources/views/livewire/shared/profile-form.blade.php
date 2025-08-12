<div class="container-fluid">
    <div class="row">
        @if($success)
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ $success }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if($error)
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ $error }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <!-- Première colonne : Informations personnelles, Photo de profil, Mot de passe -->
        <div class="col-lg-8">
            <!-- Informations personnelles -->
            <x-shared.profile-personal-info 
                :first_name="$first_name"
                :last_name="$last_name" 
                :email="$email"
                :phone_number="$phone_number"
                :is_customer="$this->isCustomer()" />

            <!-- Photo de profil -->
            <x-shared.profile-avatar 
                :avatar="$avatar"
                :current_avatar_url="$current_avatar_url" />

            <!-- Mot de passe -->
            <x-shared.profile-password 
                :current_password="$current_password"
                :password="$password"
                :password_confirmation="$password_confirmation" />
        </div>

        <!-- Deuxième colonne : Code de parrainage -->
        <div class="col-lg-4">
            <x-shared.profile-referral 
                :affiliation_code="$affiliation_code"
                :referrals_count="$referrals_count"
                :is_customer="$this->isCustomer()" />

            <!-- Préférences linguistiques -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">{{ __('profile.language_preferences') }}</h5>
                </div>
                <div class="card-body">
                    <form wire:submit.prevent="updateLocale">
                        <div class="mb-3">
                            <label for="locale" class="form-label">{{ __('profile.select_preferred_language') }}</label>
                            <select class="form-select" id="locale" wire:model="locale">
                                <option value="en">English</option>
                                <option value="fr">Français</option>
                            </select>
                            @error('locale') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <button type="submit" class="btn btn-whatsapp">{{ __('profile.update') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>