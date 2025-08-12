<?php

namespace App\Providers;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use MyCoolPay\Logging\Logger;
use MyCoolPay\MyCoolPayClient;

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

        // WhatsApp Services
        \App\Services\WhatsApp\AI\WhatsAppAIProcessorServiceInterface::class => \App\Services\WhatsApp\AI\WhatsAppAIProcessorService::class,

    ];

    public function register(): void
    {
        $this->app->bind(MyCoolPayClient::class, function () {
            $logger = new Logger('mycoolpay_webhook.log', storage_path('logs'));

            return new MyCoolPayClient(
                config('services.mycoolpay.public_key'),
                config('services.mycoolpay.private_key'),
                $logger,
                config('app.debug')
            );
        });
    }

    /**
     * @return array<class-string>
     */
    public function provides(): array
    {
        return array_keys($this->bindings);
    }
}
