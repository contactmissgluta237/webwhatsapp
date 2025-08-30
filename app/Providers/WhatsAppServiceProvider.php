<?php

declare(strict_types=1);

namespace App\Providers;

use App\Handlers\WhatsApp\WhatsAppSessionSyncHandler;
use App\Services\WhatsApp\AIProviderService;
use App\Services\WhatsApp\Contracts\AIProviderServiceInterface;
use App\Services\WhatsApp\Contracts\MessageBuildServiceInterface;
use App\Services\WhatsApp\Contracts\ResponseFormatterServiceInterface;
use App\Services\WhatsApp\Contracts\WhatsAppMessageOrchestratorInterface;
use App\Services\WhatsApp\Helpers\AIResponseParserHelper;
use App\Services\WhatsApp\Helpers\ResponseTimingHelper;
use App\Services\WhatsApp\MessageBuildService;
use App\Services\WhatsApp\ResponseFormatterService;
use App\Services\WhatsApp\WhatsAppMessageOrchestrator;
use App\Services\WhatsApp\WhatsAppNotificationHandler;
use Illuminate\Support\ServiceProvider;

final class WhatsAppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerCoreServices();
        $this->registerUtilityServices();
    }

    public function boot(): void
    {
        //
    }

    private function registerCoreServices(): void
    {
        $this->app->bind(MessageBuildServiceInterface::class, MessageBuildService::class);
        $this->app->bind(AIProviderServiceInterface::class, AIProviderService::class);
        $this->app->bind(ResponseFormatterServiceInterface::class, ResponseFormatterService::class);

        $this->app->singleton(WhatsAppMessageOrchestratorInterface::class, function ($app) {
            return new WhatsAppMessageOrchestrator(
                $app->make(MessageBuildServiceInterface::class),
                $app->make(AIProviderServiceInterface::class),
                $app->make(AIResponseParserHelper::class),
                $app->make(ResponseTimingHelper::class),
            );
        });
    }

    private function registerUtilityServices(): void
    {
        $this->app->singleton(WhatsAppSessionSyncHandler::class, function ($app) {
            return new WhatsAppSessionSyncHandler(
                bridgeBaseUrl: config('whatsapp.bridge.base_url'),
                timeout: config('whatsapp.bridge.timeout', 30)
            );
        });

        $this->app->singleton(WhatsAppNotificationHandler::class);
    }
}
