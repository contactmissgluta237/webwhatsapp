<?php

// app/Services/Cache/LaravelCacheService.php

namespace App\Services\Shared\Cache;

use Illuminate\Contracts\Cache\Repository;

class LaravelCacheService implements CacheServiceInterface
{
    public function __construct(
        private readonly Repository $cache,
        private readonly string $prefix = 'cache'
    ) {}

    public function remember(string $key, int $ttl, callable $callback): mixed
    {
        return $this->cache->remember(
            $this->getKey($key),
            $ttl,
            $callback
        );
    }

    public function put(string $key, mixed $value, int $ttl): bool
    {
        return $this->cache->put($this->getKey($key), $value, $ttl);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->cache->get($this->getKey($key), $default);
    }

    public function forget(string $key): bool
    {
        return $this->cache->forget($this->getKey($key));
    }

    public function forgetPattern(string $pattern): bool
    {
        $keys = $this->cache->get($this->getKey('_keys')) ?? [];
        $matchingKeys = array_filter($keys, fn ($key) => str_contains($key, $pattern));

        foreach ($matchingKeys as $key) {
            $this->cache->forget($key);
        }

        return true;
    }

    public function has(string $key): bool
    {
        return $this->cache->has($this->getKey($key));
    }

    private function getKey(string $key): string
    {
        return "{$this->prefix}:{$key}";
    }
}
