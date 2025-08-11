{{-- resources/views/livewire/components/phone-input.blade.php --}}
<div class="mb-3">
    <label for="{{ $name }}" class="form-label">
        {{ $label }}
        @if ($required)
            <span class="text-danger">*</span>
        @endif
    </label>

    <div class="position-relative">
        <div class="input-group">
            <!-- Country Selector -->
            <div class="position-relative">
                <button type="button" wire:click="toggleDropdown"
                    class="btn btn-outline-secondary dropdown-toggle d-flex align-items-center gap-2">
                    @if ($this->selectedCountry)
                        <span>{{ $this->selectedCountry->flag_emoji }}</span>
                        <span class="d-none d-sm-inline">{{ $this->selectedCountry->phone_code }}</span>
                    @endif
                </button>

                <!-- Dropdown -->
                @if ($showDropdown)
                    <div class="dropdown-menu show position-absolute top-100 start-0 mt-1" style="width: 320px; z-index: 1050;">
                        <!-- Search -->
                        <div class="p-2">
                            <input type="text" wire:model.live="searchCountry" placeholder="Rechercher un pays..."
                                class="form-control form-control-sm">
                        </div>

                        <!-- Countries list -->
                        <div style="max-height: 240px; overflow-y: auto;">
                            @forelse($filteredCountries as $country)
                                <button type="button" wire:click="selectCountry({{ $country->id }})"
                                    class="dropdown-item d-flex align-items-center gap-3 {{ $selectedCountryId === $country->id ? 'active' : '' }}">
                                    <span>{{ $country->flag_emoji }}</span>
                                    <span class="flex-grow-1">{{ $country->name }}</span>
                                    <span class="text-muted">{{ $country->phone_code }}</span>
                                </button>
                            @empty
                                <div class="dropdown-item-text text-muted">
                                    Aucun pays trouvé
                                </div>
                            @endforelse
                        </div>
                    </div>
                @endif
            </div>

            <!-- Phone Number Input -->
            <input type="tel" id="{{ $name }}" wire:model.live="phoneNumber" placeholder="123456789"
                class="form-control {{ $error ? 'is-invalid' : '' }}"
                {{ $required ? 'required' : '' }}>

        </div>

        <!-- Full number preview -->
        @if ($fullPhoneNumber)
            <div class="form-text">
                Numéro complet: <strong>{{ $fullPhoneNumber }}</strong>
            </div>
        @endif

        <!-- Error message -->
        @if ($error)
            <div class="invalid-feedback d-block">{{ $error }}</div>
        @endif
    </div>

    <!-- Click outside to close dropdown -->
    @if ($showDropdown)
        <div wire:click="toggleDropdown" class="position-fixed top-0 start-0 w-100 h-100" style="z-index: 1040;"></div>
    @endif
</div>
