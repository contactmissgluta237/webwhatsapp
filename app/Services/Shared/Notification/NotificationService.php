<?php

namespace App\Services\Shared\Notification;

use App\Models\User;
use Illuminate\Support\Collection as SupportCollection;

class NotificationService
{
    public function getUnreadNotifications(User $user, int $limit = 10): SupportCollection
    {
        return $user->unreadNotifications()
            ->orderBy('created_at', 'desc')
            ->take($limit)
            ->get();
    }

    public function getAllNotifications(User $user): SupportCollection
    {
        return $user->notifications()
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getLatestNotifications(User $user, int $limit = 20): SupportCollection
    {
        return $user->notifications()
            ->orderBy('created_at', 'desc')
            ->take($limit)
            ->get();
    }

    public function getUnreadCount(User $user): int
    {
        return $user->unreadNotifications()->count();
    }

    public function markAsRead(User $user, string $notificationId): void
    {
        $user->unreadNotifications()
            ->where('id', $notificationId)
            ->first()?->markAsRead();
    }

    public function markAllAsRead(User $user): void
    {
        $user->unreadNotifications()->update(['read_at' => now()]);
    }

    public function deleteNotification(User $user, string $notificationId): bool
    {
        $notification = $user->notifications()->where('id', $notificationId)->first();

        if ($notification) {
            return $notification->delete();
        }

        return false;
    }
}
