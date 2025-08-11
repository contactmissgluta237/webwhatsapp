<li class="header-notification">
    <a aria-controls="notificationcanvasRight" class="d-block head-icon position-relative"
        data-bs-target="#notificationcanvasRight" data-bs-toggle="offcanvas" href="#" role="button">
        <i class="iconoir-bell"></i>
        @if ($unreadCount > 0)
            <span
                class="position-absolute translate-middle p-1 bg-success border border-light rounded-circle animate__animated animate__fadeIn animate__infinite animate__slower">
                <span class="visually-hidden">{{ $unreadCount }}</span>
            </span>
        @endif
    </a>

    <div aria-labelledby="notificationcanvasRightLabel" class="offcanvas offcanvas-end header-notification-canvas"
        id="notificationcanvasRight" tabindex="-1" wire:ignore.self>
        <div class="offcanvas-header">
            <h5 class="offcanvas-title" id="notificationcanvasRightLabel">
                Notifications
                @if ($unreadCount > 0)
                    <span class="badge bg-primary rounded-pill">{{ $unreadCount }}</span>
                @endif
            </h5>
            @if ($unreadCount > 0)
                <button wire:click="markAllAsRead" class="btn btn-sm btn-primary ms-auto me-2">Marquer tout comme
                    lu</button>
            @endif
            <button aria-label="Close" class="btn-close" data-bs-dismiss="offcanvas" type="button"></button>
        </div>

        <div class="offcanvas-body notification-offcanvas-body app-scroll p-0">
            <div class="head-container notification-head-container">
                @forelse($notifications as $notification)
                    @php
                        $notificationType = NotificationType::tryFrom($notification->data['type'] ?? '');
                    @endphp
                    <div class="notification-message head-box {{ $notification->unRead() ? 'unread' : '' }}"
                        wire:click="handleNotificationClick('{{ $notification->id }}')" style="cursor: pointer"
                        wire:key="notification-{{ $notification->id }}">
                        <div class="message-images">

                            <span
                                class="{{ $notificationType?->getBadgeClass() }} h-35 w-35 d-flex-center b-r-10 position-relative">
                                <i class="ph-duotone {{ $notificationType?->getIcon() }} f-s-18"></i>
                            </span>
                        </div>

                        <div class="message-content-box flex-grow-1 ps-2">
                            <div class="f-s-15 mb-0">
                                >
                                <span class="f-w-500">{{ $notificationType?->label }}</span>
                            </div>

                            <div class="notification-details">
                                <span class="d-inline-block f-w-500 me-1">
                                    Réf: <span
                                        class="text-primary">{{ $notification->data['order_number'] ?? 'N/A' }}</span>
                                </span>
                                @if (isset($notification->data['customer_name']))
                                    |
                                    <span class="d-inline-block f-w-500 ms-1">
                                        Client: <span
                                            class="text-primary">{{ $notification->data['customer_name'] }}</span>
                                    </span>
                                @endif
                                @if (isset($notification->data['total_amount']))
                                    |
                                    <span class="d-inline-block f-w-500 ms-1">
                                        Total: <span
                                            class="text-primary">{{ number_format($notification->data['total_amount']) }}
                                            FCFA</span>
                                    </span>
                                @endif
                            </div>

                            <div class="notification-message-text">
                                {{ $notification->data['message'] ?? '' }}
                            </div>

                            <span class="{{ $notificationType?->getBadgeClass() }} badge mt-2">
                                {{ $notification->time_ago }}
                            </span>
                        </div>

                        <div class="align-self-start text-end">
                            <button wire:click.stop="markAsRead('{{ $notification->id }}')"
                                class="btn btn-sm btn-link p-0 text-danger" title="Marquer comme lu">
                                <i class="iconoir-xmark f-s-24"></i>
                            </button>
                        </div>
                    </div>
                @empty
                    <div class="hidden-massage py-4 px-3 text-center">
                        <img alt="No notifications" class="w-50 h-50 mb-3 mt-2"
                            src="{{ asset('assets/images/icons/bell.png') }}">
                        <div>
                            <h6 class="mb-0">Aucune notification</h6>
                            <p class="text-muted">Vous n'avez pas de nouvelles notifications concernant les commandes.
                            </p>
                        </div>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</li>

@push('styles')
    <style>
        .notification-message.unread {
            background-color: #f8f9ff;
            border-left: 3px solid #4f46e5;
        }

        .notification-message {
            transition: all 0.2s ease;
        }

        .notification-message:hover {
            background-color: #f8f9fa;
        }

        .cursor-pointer {
            cursor: pointer;
        }
    </style>
@endpush

@push('scripts')
    <script>
        document.addEventListener('livewire:init', () => {
            // Rafraîchir les notifications toutes les 10 minutes
            setInterval(() => {
                @this.call('refreshNotifications');
            }, 600000); // 10 minutes
        });
    </script>
@endpush
