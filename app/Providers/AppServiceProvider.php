<?php

namespace App\Providers;

use App\Channels\PushNotificationChannel;
use App\Contracts\PromptEnhancementInterface;
use App\Models\Ticket;
use App\Models\UserProduct;
use App\Policies\TicketPolicy;
use App\Policies\UserProductPolicy;
use App\Services\AI\PromptEnhancementService;
use App\Services\Shared\Media\MediaService;
use App\Services\Shared\Media\MediaServiceInterface;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(MediaServiceInterface::class, MediaService::class);
        $this->app->bind(PromptEnhancementInterface::class, PromptEnhancementService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Ticket::class, TicketPolicy::class);
        Gate::policy(UserProduct::class, UserProductPolicy::class);

        $this->app->make(ChannelManager::class)->extend('push', function () {
            return new PushNotificationChannel;
        });
    }
}
