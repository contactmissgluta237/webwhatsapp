<?php

namespace App\Livewire\Components;

use App\Enums\NotificationType;
use App\Services\Shared\Notification\NotificationService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;
use Livewire\Component;

class NotificationHeader extends Component
{
    public Collection $notifications;
    public int $unreadCount = 0;
    public bool $showingAll = false;
    public int $limit = 20;

    protected NotificationService $notificationService;

    public function boot(NotificationService $notificationService): void
    {
        $this->notificationService = $notificationService;
    }

    public function mount(): void
    {
        $this->loadNotifications();
    }

    public function loadNotifications(): void
    {
        $user = auth()->user();

        if ($this->showingAll) {
            $this->notifications = $this->notificationService->getAllNotifications($user);
        } else {
            $this->notifications = $this->notificationService->getUnreadNotifications($user, $this->limit);
        }

        $this->unreadCount = $this->notificationService->getUnreadCount($user);
    }

    public function showAllNotifications(): void
    {
        $this->showingAll = true;
        $this->loadNotifications();
    }

    public function showRecentOnly(): void
    {
        $this->showingAll = false;
        $this->loadNotifications();
    }

    public function handleNotificationClick(string $notificationId)
    {
        $notification = auth()->user()->notifications()->find($notificationId);

        if ($notification) {
            $this->markAsRead($notificationId);

            $url = $notification->data['url'] ?? null;
            if ($url) {
                return redirect($url);
            }
        }
    }

    public function markAsRead(string $notificationId): void
    {
        $user = auth()->user();

        try {
            $this->notificationService->markAsRead($user, $notificationId);
            $this->loadNotifications();
            $this->dispatch('notification-updated');
        } catch (\Exception $e) {
            Log::warning('Erreur lors du marquage de la notification comme lue', [
                'notification_id' => $notificationId,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function markAllAsRead(): void
    {
        $user = auth()->user();

        try {
            $this->notificationService->markAllAsRead($user);
            $this->loadNotifications();
            $this->dispatch('notification-updated');
        } catch (\Exception $e) {
            Log::warning('Erreur lors du marquage de toutes les notifications comme lues', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function deleteNotification(string $notificationId): void
    {
        $user = auth()->user();

        try {
            $this->notificationService->deleteNotification($user, $notificationId);
            $this->loadNotifications();
            $this->dispatch('notification-updated');
        } catch (\Exception $e) {
            Log::warning('Erreur lors de la suppression de la notification', [
                'notification_id' => $notificationId,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function getNotificationType($notification): NotificationType
    {
        $typeClass = $notification->type;

        if (str_contains($typeClass, 'OrderNotification')) {
            $typeValue = $notification->data['type'] ?? 'order_created';

            return NotificationType::from($typeValue);
        }

        return NotificationType::ORDER_CREATED();
    }

    #[On('refresh-notifications')]
    public function refreshNotifications(): void
    {
        $this->loadNotifications();
    }

    public function render()
    {
        return view('livewire.components.notification-header', [
            'notifications' => $this->notifications,
            'unreadCount' => $this->unreadCount,
            'showingAll' => $this->showingAll,
        ]);
    }
}
