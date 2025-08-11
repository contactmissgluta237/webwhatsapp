<form wire:submit.prevent="save">
        <div class="row">
            <div class="col-md-6 mb-1">
                <label for="first_name" class="form-label">{{ __('First Name') }}</label>
                <input type="text" class="form-control" id="first_name" wire:model="first_name">
                @error('first_name') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
            <div class="col-md-6 mb-1">
                <label for="last_name" class="form-label">{{ __('Last Name') }}</label>
                <input type="text" class="form-control" id="last_name" wire:model="last_name">
                @error('last_name') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-1">
                <label for="email" class="form-label">{{ __('Email') }}</label>
                <input type="email" class="form-control" id="email" wire:model="email">
                @error('email') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
            <div class="col-md-6 mb-1">
                <livewire:components.phone-input 
                    name="phone_number"
                    label="{{ __('Phone Number') }}"
                    :required="true"
                    :value="$phone_number ?: null"
                    :default-country-id="$country_id ?? 1"
                    :error="$errors->first('phone_number')"
                    wire:key="phone-input-admin-form"
                />
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-1">
                <label for="password" class="form-label">{{ __('Password') }}</label>
                <div class="input-group">
                    <input type="{{ $showPassword ? 'text' : 'password' }}" class="form-control" id="password" wire:model="password">
                    <button class="btn btn-outline-secondary" type="button" wire:click="toggleShowPassword">
                        <i class="ti {{ $showPassword ? 'ti-eye-off' : 'ti-eye' }}"></i>
                    </button>
                </div>
                @error('password') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
            <div class="col-md-6 mb-1">
                <label for="roles" class="form-label">{{ __('Roles') }}</label>
                <select class="form-select w-100" id="roles" wire:model="selectedRoles" multiple>
                    @foreach($allRoles as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                @error('selectedRoles') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>

        <div class="mb-1 form-check">
            <input type="checkbox" class="form-check-input" id="is_active" wire:model="is_active">
            <label class="form-check-label" for="is_active">{{ __('Active') }}</label>
        </div>

        <div class="mb-1 custom-file">
            <label for="image" class="custom-file-label">{{ __('Profile Image') }}</label>
            <input type="file" class="custom-file-input" id="image" wire:model="image">
            @error('image') <span class="text-danger">{{ $message }}</span> @enderror
            @if ($image && !$errors->has('image'))
                <div class="mt-2" style="width: 100px; height: 100px; background-size: cover; background-position: center; {{ $this->getImagePreviewStyleProperty() }}"></div>
            @endif
        </div>

    <div class="d-flex justify-content-end">
        <button type="submit" class="btn btn-info">{{ __('Save') }}</button>
    </div>
</form>

<script>
document.addEventListener('livewire:initialized', function () {
    setTimeout(() => {
        Livewire.dispatch('initializePhone');
    }, 250);
});
</script>
