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
     * Formats a price according to a currency using centralized config.
     */
    public function formatPrice(float $amount, ?string $currencyCode = null): string
    {
        $currencyCode = $currencyCode ?? $this->getDefaultCurrency();

        return $this->format($amount, $currencyCode);
    }

    /**
     * Get currency symbol from config.
     */
    public function getSymbol(string $currencyCode): string
    {
        return config("currencies.symbols.{$currencyCode}", $currencyCode);
    }

    /**
     * Format amount with centralized currency configuration.
     */
    public function format(float $amount, ?string $currencyCode = null): string
    {
        $currencyCode = $currencyCode ?? $this->getDefaultCurrency();
        $config = config("currencies.formatting.{$currencyCode}");
        $symbol = $this->getSymbol($currencyCode);

        if (! $config) {
            // Fallback to basic formatting for unsupported currencies
            return number_format($amount, 2).' '.$symbol;
        }

        $formatted = number_format(
            $amount,
            $config['decimals'],
            $config['decimal_separator'],
            $config['thousands_separator']
        );

        return $config['position'] === 'before'
            ? "{$symbol} {$formatted}"
            : "{$formatted} {$symbol}";
    }

    /**
     * Get exchange rate between two currencies.
     */
    public function getExchangeRate(string $from, string $to): float
    {
        if ($from === $to) {
            return 1.0;
        }

        $key = strtoupper("{$from}_TO_{$to}");

        return config("currencies.exchange_rates.{$key}", 1.0);
    }

    /**
     * Convert amount from one currency to another.
     */
    public function convert(float $amount, string $from, string $to): float
    {
        if ($from === $to) {
            return $amount;
        }

        $rate = $this->getExchangeRate($from, $to);

        return $amount * $rate;
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
        return config('currencies.default', 'USD');
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
