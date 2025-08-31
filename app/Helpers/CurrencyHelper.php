<?php

declare(strict_types=1);

namespace App\Helpers;

final class CurrencyHelper
{
    private const USD_TO_XAF_RATE = 600.0;
    private const USD_TO_EUR_RATE = 0.92;

    public static function usdToXaf(float $usd): float
    {
        return $usd * self::USD_TO_XAF_RATE;
    }

    public static function xafToUsd(float $xaf): float
    {
        return $xaf / self::USD_TO_XAF_RATE;
    }

    public static function formatUsd(float $amount): string
    {
        return number_format($amount, 2, '.', '').' $';
    }

    public static function convertForGateway(float $usd, string $gateway, string $paymentMethod): array
    {
        $currency = config("currencies.gateway_mappings.{$gateway}.{$paymentMethod}", 'XAF');

        $amount = match ($currency) {
            'XAF' => self::usdToXaf($usd),
            'EUR' => $usd * self::USD_TO_EUR_RATE,
            default => $usd
        };

        return ['amount' => $amount, 'currency' => $currency];
    }
}
