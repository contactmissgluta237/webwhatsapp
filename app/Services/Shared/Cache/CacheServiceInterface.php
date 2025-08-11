<?php

namespace App\Services\Shared\Cache;

interface CacheServiceInterface
{
    /**
     * Get cached data or execute callback
     */
    public function remember(string $key, int $ttl, callable $callback): mixed;

    /**
     * Store data in cache
     */
    public function put(string $key, mixed $value, int $ttl): bool;

    /**
     * Get data from cache
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Remove data from cache
     */
    public function forget(string $key): bool;

    /**
     * Clear cache by pattern
     */
    public function forgetPattern(string $pattern): bool;

    /**
     * Check if key exists in cache
     */
    public function has(string $key): bool;
}
