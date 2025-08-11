<?php

namespace App\Livewire\Components;

use App\Helpers\CardHelper;
use Livewire\Component;

class CardInput extends Component
{
    public string $name = 'card_number';
    public string $label = 'Numéro de carte';
    public bool $required = false;
    public ?string $error = null;

    public string $cardNumber = '';
    public string $cvv = '';
    public string $expiryMonth = '';
    public string $expiryYear = '';
    public string $maskedCardNumber = '';
    public bool $isValid = false;

    public array $validationErrors = [];

    protected $listeners = ['resetCardInput'];

    public function mount(?string $value = null): void
    {
        if ($value) {
            $this->cardNumber = $value;
            $this->updateMaskedNumber();
        }
    }

    public function updatedCardNumber(): void
    {
        $this->cardNumber = CardHelper::formatCardNumber($this->cardNumber);
        $this->updateMaskedNumber();
        $this->validateCard();
        $this->dispatchCardUpdated();
    }

    public function updatedCvv(): void
    {
        // Limit to 3 digits maximum and keep only digits
        $this->cvv = substr(preg_replace('/[^0-9]/', '', $this->cvv), 0, 3);
        $this->validateCard();
        $this->dispatchCardUpdated();
    }

    public function updatedExpiryMonth(): void
    {
        // Limit to 2 digits and validate the month (01-12)
        $month = substr(preg_replace('/[^0-9]/', '', $this->expiryMonth), 0, 2);
        if ($month !== '') {
            $monthInt = intval($month);
            if ($monthInt > 12) {
                $month = '12';
            } elseif ($monthInt < 1 && strlen($month) === 2) {
                $month = '01';
            }
        }
        $this->expiryMonth = $month;
        $this->validateCard();
        $this->dispatchCardUpdated();
    }

    public function updatedExpiryYear(): void
    {
        // Limit to 2 digits
        $this->expiryYear = substr(preg_replace('/[^0-9]/', '', $this->expiryYear), 0, 2);
        $this->validateCard();
        $this->dispatchCardUpdated();
    }

    private function updateMaskedNumber(): void
    {
        $cleanNumber = preg_replace('/[^0-9]/', '', $this->cardNumber);
        if (strlen($cleanNumber) >= 10) {
            $this->maskedCardNumber = CardHelper::maskCardNumber($cleanNumber);
        } else {
            $this->maskedCardNumber = '';
        }
    }

    private function validateCard(): void
    {
        $this->validationErrors = [];
        $cleanNumber = preg_replace('/[^0-9]/', '', $this->cardNumber);

        // Validate card number
        if (empty($cleanNumber)) {
            $this->validationErrors['cardNumber'] = 'Le numéro de carte est requis';
        } elseif (strlen($cleanNumber) < 16) {
            $this->validationErrors['cardNumber'] = 'Le numéro de carte doit contenir 16 chiffres';
        } elseif (strlen($cleanNumber) > 16) {
            $this->validationErrors['cardNumber'] = 'Le numéro de carte ne peut pas dépasser 16 chiffres';
        } elseif (! CardHelper::validateCardNumber($cleanNumber)) {
            $this->validationErrors['cardNumber'] = 'Le numéro de carte n\'est pas valide';
        }

        // Validate CVV
        if (empty($this->cvv)) {
            $this->validationErrors['cvv'] = 'Le CVV est requis';
        } elseif (strlen($this->cvv) !== 3) {
            $this->validationErrors['cvv'] = 'Le CVV doit contenir exactement 3 chiffres';
        }

        // Validate expiry month
        if (empty($this->expiryMonth)) {
            $this->validationErrors['expiryMonth'] = 'Le mois d\'expiration est requis';
        } elseif (strlen($this->expiryMonth) !== 2) {
            $this->validationErrors['expiryMonth'] = 'Le mois doit contenir 2 chiffres';
        } elseif (intval($this->expiryMonth) < 1 || intval($this->expiryMonth) > 12) {
            $this->validationErrors['expiryMonth'] = 'Le mois doit être entre 01 et 12';
        }

        // Validate expiry year
        if (empty($this->expiryYear)) {
            $this->validationErrors['expiryYear'] = 'L\'année d\'expiration est requise';
        } elseif (strlen($this->expiryYear) !== 2) {
            $this->validationErrors['expiryYear'] = 'L\'année doit contenir 2 chiffres';
        } else {
            $currentYear = intval(date('y'));
            $yearInt = intval($this->expiryYear);
            if ($yearInt < $currentYear) {
                $this->validationErrors['expiryYear'] = 'La carte a expiré';
            }
        }

        // Check if the card has expired (month/year)
        if (empty($this->validationErrors['expiryMonth']) && empty($this->validationErrors['expiryYear'])) {
            $currentYear = intval(date('y'));
            $currentMonth = intval(date('m'));
            $yearInt = intval($this->expiryYear);
            $monthInt = intval($this->expiryMonth);

            if ($yearInt === $currentYear && $monthInt < $currentMonth) {
                $this->validationErrors['expiry'] = 'La carte a expiré';
            }
        }

        $this->isValid = empty($this->validationErrors);
    }

    private function dispatchCardUpdated(): void
    {
        $cleanNumber = preg_replace('/[^0-9]/', '', $this->cardNumber);

        $this->dispatch('cardUpdated', [
            'name' => $this->name,
            'card_number' => $cleanNumber,
            'masked_card_number' => $this->maskedCardNumber,
            'cvv' => $this->cvv,
            'expiry_month' => $this->expiryMonth,
            'expiry_year' => $this->expiryYear,
            'is_valid' => $this->isValid,
            'validation_errors' => $this->validationErrors,
        ]);
    }

    public function resetCardInput(): void
    {
        $this->cardNumber = '';
        $this->cvv = '';
        $this->expiryMonth = '';
        $this->expiryYear = '';
        $this->maskedCardNumber = '';
        $this->isValid = false;
        $this->validationErrors = [];
    }

    public function render()
    {
        return view('livewire.components.card-input');
    }
}
