<?php

namespace App\Services\User;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class UserPresenceService
{
    private const CACHE_PREFIX = 'user_online_';
    private const ONLINE_THRESHOLD_SECONDS = 300; // 5 minutes

    /**
     * Mark a user as online.
     */
    public function markUserOnline(int $userId): void
    {
        Cache::put(
            self::CACHE_PREFIX.$userId,
            now(),
            self::ONLINE_THRESHOLD_SECONDS
        );
    }

    /**
     * Check if a user is currently online.
     */
    public function isUserOnline(int $userId): bool
    {
        $lastSeen = Cache::get(self::CACHE_PREFIX.$userId);

        if (! $lastSeen) {
            return false;
        }

        return now()->diffInSeconds($lastSeen) < self::ONLINE_THRESHOLD_SECONDS;
    }

    /**
     * Mark a user as offline.
     */
    public function markUserOffline(int $userId): void
    {
        Cache::forget(self::CACHE_PREFIX.$userId);
    }

    /**
     * Get the last activity timestamp.
     */
    public function getLastActivity(int $userId): ?Carbon
    {
        return Cache::get(self::CACHE_PREFIX.$userId);
    }

    /**
     * Clean up inactive users (optional, for maintenance).
     */
    public function cleanupInactiveUsers(): int
    {
        // This method could be called by a scheduled job
        // For now, the cache expires automatically
        return 0;
    }
}
