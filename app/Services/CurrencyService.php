<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Geography\Country;
use App\Models\User;

final class CurrencyService
{
    /**
     * Retrieves the default currency for a given country code.
     */
    public function getCurrencyByCountry(string $countryCode): string
    {
        $mapping = config('currencies.country_currency_mapping', []);

        return $mapping[strtoupper($countryCode)] ?? config('currencies.default_currency', 'XAF');
    }

    /**
     * Retrieves the default currency for a country by its ID.
     */
    public function getCurrencyByCountryId(int $countryId): string
    {
        $country = Country::find($countryId);

        if (! $country || ! $country->code) {
            return config('currencies.default_currency', 'XAF');
        }

        return $this->getCurrencyByCountry($country->code);
    }

    /**
     * Retrieves a user's currency.
     */
    public function getUserCurrency(User $user): string
    {
        if (! empty($user->currency)) {
            return $user->currency;
        }

        if ($user->country_id) {
            return $this->getCurrencyByCountryId($user->country_id);
        }

        // Fallback to default currency
        return config('currencies.default_currency');
    }

    /**
     * Updates a user's currency based on their country.
     */
    public function updateUserCurrencyByCountry(User $user): void
    {
        if (! $user->country_id) {
            return;
        }

        $currency = $this->getCurrencyByCountryId($user->country_id);

        $user->update(['currency' => $currency]);
    }

    /**
     * Formats a price according to a currency.
     */
    public function formatPrice(float $amount, string $currencyCode): string
    {
        $currencyInfo = config("currencies.currencies.{$currencyCode}");

        if (! $currencyInfo) {
            // Fallback to XAF if currency is unknown
            $currencyInfo = config('currencies.currencies.XAF');
            $currencyCode = 'XAF';
        }

        $decimals = $currencyInfo['decimals'] ?? 0;
        $symbol = $currencyInfo['symbol'] ?? $currencyCode;

        $formattedAmount = number_format($amount, $decimals, ',', ' ');

        // For XAF/XOF, the symbol goes after
        if (in_array($currencyCode, ['XAF', 'XOF'])) {
            return "{$formattedAmount} {$symbol}";
        }

        // For other currencies, symbol goes before
        return "{$symbol} {$formattedAmount}";
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
        return config('currencies.default_currency', 'XAF');
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
