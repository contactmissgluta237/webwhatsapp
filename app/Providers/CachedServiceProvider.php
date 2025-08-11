<?php

namespace App\Providers;

use App\Services\Shared\Cache\CacheServiceInterface;
use App\Services\Shared\Cache\LaravelCacheService;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\ServiceProvider;

class CachedServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(CacheServiceInterface::class, function ($app) {
            return new LaravelCacheService(
                $app->make(Repository::class),
            );
        });
    }

    public function boot(): void
    {
        //
    }
}
