<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Geography\Country;
use App\Models\User;

final class CurrencyService
{
    /**
     * Retrieves the default currency for a country by its ID.
     */
    public function getCurrencyByCountryId(int $countryId): string
    {
        $country = Country::find($countryId);

        if (! $country || ! $country->code) {
            return $this->getDefaultCurrency();
        }

        return $this->getDefaultCurrency();
    }

    /**
     * Retrieves a user's currency.
     */
    public function getUserCurrency(User $user): string
    {
        // Toujours retourner USD maintenant
        return $this->getDefaultCurrency();
    }

    /**
     * Formats a price according to a currency.
     */
    public function formatPrice(float $amount, string $currencyCode = 'USD'): string
    {
        return match ($currencyCode) {
            'USD' => \App\Helpers\CurrencyHelper::formatUsd($amount),
            'XAF' => number_format($amount, 0, '.', ' ').' XAF',
            'EUR' => number_format($amount, 2, '.', ' ').' €',
            default => number_format($amount, 2).' '.$currencyCode
        };
    }

    /**
     * Retrieves all available currencies.
     */
    public function getAllCurrencies(): array
    {
        return config('currencies.currencies', []);
    }

    /**
     * Retrieves information about a specific currency.
     */
    public function getCurrencyInfo(string $currencyCode): ?array
    {
        return config("currencies.currencies.{$currencyCode}");
    }

    /**
     * Checks if a currency is supported.
     */
    public function isCurrencySupported(string $currencyCode): bool
    {
        return ! empty(config("currencies.currencies.{$currencyCode}"));
    }

    /**
     * Retrieves the system's default currency.
     */
    public function getDefaultCurrency(): string
    {
        return config('currencies.default_currency', 'USD');
    }

    /**
     * Sets the currency for a new user during registration.
     */
    public function setCurrencyForNewUser(User $user, ?int $countryId = null): void
    {
        $countryId = $countryId ?? $user->country_id;

        if ($countryId) {
            $currency = $this->getCurrencyByCountryId($countryId);
            $user->update(['currency' => $currency]);
        } else {
            $user->update(['currency' => $this->getDefaultCurrency()]);
        }
    }
}
