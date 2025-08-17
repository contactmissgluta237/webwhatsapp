<?php

namespace App\Providers;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class ServiceServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * @var array<class-string, class-string>
     */
    public array $bindings = [
        \App\Services\SMS\SmsServiceInterface::class => \App\Services\SMS\TwilioService::class,
        \App\Services\Shared\Cache\CacheServiceInterface::class => \App\Services\Shared\Cache\LaravelCacheService::class,
        \App\Services\Auth\Contracts\OtpServiceInterface::class => \App\Services\Auth\OtpService::class,
        \App\Services\Auth\Contracts\AccountActivationServiceInterface::class => \App\Services\Auth\AccountActivationService::class,
        \App\Services\Payment\MyCoolPay\Contracts\MyCoolPayWebhookServiceInterface::class => \App\Services\Payment\MyCoolPay\MyCoolPayWebhookService::class,
    ];

    public function register(): void
    {
        //
    }

    /**
     * @return array<class-string>
     */
    public function provides(): array
    {
        return array_keys($this->bindings);
    }
}
