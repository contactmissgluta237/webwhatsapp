<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\WhatsApp\AIProviderServiceInterface;
use App\Contracts\WhatsApp\ContextPreparationServiceInterface;
use App\Contracts\WhatsApp\MessageBuildServiceInterface;
use App\Contracts\WhatsApp\ResponseFormatterServiceInterface;
use App\Contracts\WhatsApp\WhatsAppMessageOrchestratorInterface;
use App\Services\WhatsApp\AIProviderService;
use App\Services\WhatsApp\ContextPreparationService;
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
        $this->app->bind(ContextPreparationServiceInterface::class, ContextPreparationService::class);
        $this->app->bind(MessageBuildServiceInterface::class, MessageBuildService::class);
        $this->app->bind(AIProviderServiceInterface::class, AIProviderService::class);
        $this->app->bind(ResponseFormatterServiceInterface::class, ResponseFormatterService::class);

        // Register main orchestrator
        $this->app->bind(WhatsAppMessageOrchestratorInterface::class, WhatsAppMessageOrchestrator::class);

        // Register as singleton for performance
        $this->app->singleton(WhatsAppMessageOrchestratorInterface::class, function ($app) {
            return new WhatsAppMessageOrchestrator(
                $app->make(ContextPreparationServiceInterface::class),
                $app->make(MessageBuildServiceInterface::class),
                $app->make(AIProviderServiceInterface::class),
                $app->make(ResponseFormatterServiceInterface::class)
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
