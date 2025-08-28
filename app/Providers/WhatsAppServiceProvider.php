<?php

declare(strict_types=1);

namespace App\Providers;

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
use Illuminate\Support\ServiceProvider;

final class WhatsAppServiceProvider extends ServiceProvider
{
    /**
     * Register WhatsApp services
     */
    public function register(): void
    {
        // Register specialized delegate services
        $this->app->bind(MessageBuildServiceInterface::class, MessageBuildService::class);
        $this->app->bind(AIProviderServiceInterface::class, AIProviderService::class);
        $this->app->bind(ResponseFormatterServiceInterface::class, ResponseFormatterService::class);

        // Register main orchestrator
        $this->app->bind(WhatsAppMessageOrchestratorInterface::class, WhatsAppMessageOrchestrator::class);

        // Register as singleton for performance
        $this->app->singleton(WhatsAppMessageOrchestratorInterface::class, function ($app) {
            return new WhatsAppMessageOrchestrator(
                $app->make(MessageBuildServiceInterface::class),
                $app->make(AIProviderServiceInterface::class),
                $app->make(AIResponseParserHelper::class),
                $app->make(ResponseTimingHelper::class),
            );
        });
    }

    /**
     * Bootstrap services
     */
    public function boot(): void
    {
        //
    }
}
