<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use MyCoolPay\Logging\Logger;
use MyCoolPay\MyCoolPayClient;

class MyCoolPayServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
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
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
