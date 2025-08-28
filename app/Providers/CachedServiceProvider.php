<?php

namespace App\Providers;

use App\Services\Shared\Cache\CacheServiceInterface;
use App\Services\Shared\Cache\LaravelCacheService;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class CachedServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(CacheServiceInterface::class, function (Application $app): LaravelCacheService {
            return new LaravelCacheService(
                $app->make(Repository::class),
            );
        });
    }

    public function boot(): void {}
}
