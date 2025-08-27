<?php

namespace App\Livewire\Components;

use App\Models\Geography\Country;
use Illuminate\Support\Collection;
use Livewire\Component;

class PhoneInput extends Component
{
    private const DEFAULT_COUNTRY_CODE = 'CM';

    // Props from parent
    public string $name = 'phone_number';
    public string $label = 'Téléphone';
    public bool $required = false;
    public ?string $error = null;

    // Internal state
    public ?int $selectedCountryId = null;
    public string $phoneNumber = '';
    public string $fullPhoneNumber = '';
    public bool $showDropdown = false;
    public string $searchCountry = '';

    // Collections
    public Collection $countries;
    public Collection $filteredCountries;

    protected $listeners = ['resetPhoneInput', 'initializePhone'];

    public function mount(?string $value = null, ?int $defaultCountryId = null): void
    {
        $this->countries = Country::active()->ordered()->get();
        $this->filteredCountries = $this->countries;

        $this->selectedCountryId = $defaultCountryId ??
            $this->countries->where('code', self::DEFAULT_COUNTRY_CODE)->first()?->id ??
            $this->countries->first()?->id;

        if ($value) {
            $this->parsePhoneNumber($value);
        }

        $this->updateFullPhoneNumber();
        $this->dispatchPhoneUpdated();
    }

    public function initializePhone(): void
    {
        $this->updateFullPhoneNumber();
        $this->dispatchPhoneUpdated();
    }

    public function updatedSearchCountry(): void
    {
        $this->filteredCountries = $this->countries->filter(function (Country $country): bool {
            return str_contains(
                strtolower($country->name.' '.$country->phone_code),
                strtolower($this->searchCountry)
            );
        });
    }

    public function updatedPhoneNumber(): void
    {
        $cleaned = preg_replace('/[^0-9]/', '', $this->phoneNumber);

        if ($this->phoneNumber !== $cleaned) {
            $this->phoneNumber = $cleaned;
            $this->updateFullPhoneNumber();

            return;
        }

        $this->updateFullPhoneNumber();
        $this->dispatchPhoneUpdated();
    }

    public function selectCountry(int $countryId): void
    {
        $this->selectedCountryId = $countryId;
        $this->showDropdown = false;
        $this->searchCountry = '';
        $this->filteredCountries = $this->countries;
        $this->updateFullPhoneNumber();
        $this->dispatchPhoneUpdated();
    }

    public function toggleDropdown(): void
    {
        $this->showDropdown = ! $this->showDropdown;
        if (! $this->showDropdown) {
            $this->searchCountry = '';
            $this->filteredCountries = $this->countries;
        }
    }

    private function updateFullPhoneNumber(): void
    {
        $country = $this->countries->firstWhere('id', $this->selectedCountryId);

        if ($country && ! empty(trim($this->phoneNumber))) {
            $this->fullPhoneNumber = $country->phone_code.$this->phoneNumber;
        } else {
            $this->fullPhoneNumber = '';
        }
    }

    private function dispatchPhoneUpdated(): void
    {
        $this->dispatch('phoneUpdated', [
            'name' => $this->name,
            'value' => $this->fullPhoneNumber,
            'country_id' => $this->selectedCountryId,
            'phone_number' => $this->phoneNumber,
        ]);
    }

    private function parsePhoneNumber(string $value): void
    {
        foreach ($this->countries as $country) {
            if (str_starts_with($value, $country->phone_code)) {
                $this->selectedCountryId = $country->id;
                $this->phoneNumber = substr($value, strlen($country->phone_code));

                return;
            }
        }

        $this->phoneNumber = $value;
    }

    public function getSelectedCountryProperty(): ?Country
    {
        return $this->countries->firstWhere('id', $this->selectedCountryId);
    }

    public function render()
    {
        return view('livewire.components.phone-input');
    }
}
