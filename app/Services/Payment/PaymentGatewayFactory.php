<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Models\Geography\Country;
use App\Services\Payment\Contracts\PaymentGatewayInterface;
use App\Services\Payment\Exceptions\UnsupportedCountryException;
use App\Services\Payment\Gateways\MyCoolPayGateway;

final class PaymentGatewayFactory
{
    private array $countryGatewayMappings;

    public function __construct()
    {
        $this->countryGatewayMappings = config('payment-gateways.mappings', [
            'CM' => MyCoolPayGateway::class, // Cameroon
        ]);
    }

    public function fromCountry(Country $country): PaymentGatewayInterface
    {
        $gatewayClass = $this->countryGatewayMappings[$country->code] ?? null;

        if (! $gatewayClass) {
            throw new UnsupportedCountryException(
                "Payment gateway not available for {$country->name}"
            );
        }

        return app($gatewayClass);
    }

    public function isCountrySupported(Country $country): bool
    {
        return isset($this->countryGatewayMappings[$country->code]);
    }

    public function getSupportedCountries(): array
    {
        return array_keys($this->countryGatewayMappings);
    }
}
