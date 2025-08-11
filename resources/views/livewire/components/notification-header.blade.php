<li class="dropdown dropdown-notification nav-item">
    <a class="nav-link nav-link-label" href="#" data-toggle="dropdown">
        <i class="ficon ft-bell"></i>
        @if($unreadCount > 0)
            <span class="badge badge-pill badge-danger badge-up badge-glow">{{ $unreadCount }}</span>
        @endif
    </a>
    <ul class="dropdown-menu dropdown-menu-media dropdown-menu-right">
        <li class="dropdown-menu-header">
            <div class="d-flex justify-content-between align-items-center px-2 py-1">
                <div>
                    <p class="dropdown-menu-title mb-0">Notifications</p>
                    <p class="dropdown-menu-title-text small mb-0">
                        @if($showingAll)
                            Toutes les notifications
                        @else
                            {{ $unreadCount }} non lue{{ $unreadCount > 1 ? 's' : '' }}
                        @endif
                    </p>
                </div>
                @if($unreadCount > 0)
                    <button wire:click="markAllAsRead" class="btn btn-sm btn-outline-info">
                        <i class="la la-check-circle"></i> Tout marquer
                    </button>
                @endif
            </div>
        </li>
        
        <li class="scrollable-container media-list w-100 mt-1" style="max-height: 250px; overflow-y: auto;">
            @forelse($notifications as $notification)
                @php
                    $notificationType = $this->getNotificationType($notification);
                    $isUnread = $notification->unRead();
                @endphp
                <a href="{{ route('notifications.read', ['notificationId' => $notification->id]) }}" class="media notification-item {{ $isUnread ? 'unread-notification' : '' }}" wire:key="notification-{{ $notification->id }}">
                    
                    <div class="media-left align-self-start pt-1">
                        <div class="notification-icon-wrapper">
                            <i class="la {{ $notificationType->getIcon() }} {{ $notificationType->getBadgeClass() }}"></i>
                            @if($isUnread)
                                <span class="notification-badge"></span>
                            @endif
                        </div>
                    </div>
                    
                    <div class="media-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <h6 class="media-heading mb-1">{{ $notificationType->label }}</h6>
                            <div class="notification-actions">
                                
                            </div>
                        </div>
                        
                        <div class="notification-content">
                            @if(isset($notification->data['order_number']))
                                <div class="notification-meta">
                                    <span class="badge badge-light-primary">{{ $notification->data['order_number'] }}</span>
                                    @if(isset($notification->data['customer_name']))
                                        <span class="text-muted small">• {{ $notification->data['customer_name'] }}</span>
                                    @endif
                                    @if(isset($notification->data['total_amount']))
                                        <span class="text-success small font-weight-bold">• {{ number_format($notification->data['total_amount']) }} FCFA</span>
                                    @endif
                                </div>
                            @endif
                            
                            @if(isset($notification->data['message']))
                                <p class="notification-text mb-1">{{ $notification->data['message'] }}</p>
                            @endif
                            
                            <small class="text-muted">
                                <i class="la la-clock-o mr-1"></i>{{ $notification->created_at->diffForHumans() }}
                            </small>
                        </div>
                    </div>
                </a>
            @empty
                <div class="empty-notifications text-center py-4">
                    <div class="empty-notification-icon mb-3">
                        <i class="la la-bell-slash-o" style="font-size: 3rem; color: #ddd;"></i>
                    </div>
                    <h6 class="text-muted mb-2">Aucune notification</h6>
                    <p class="text-muted small mb-0">
                        @if($showingAll)
                            Vous n'avez aucune notification.
                        @else
                            Vous n'avez pas de nouvelles notifications.
                        @endif
                    </p>
                </div>
            @endforelse
        </li>
        
        <li class="dropdown-menu-footer mt-1">
            <div class="d-flex justify-content-center">
                @if($showingAll)
                    <button wire:click="showRecentOnly" class="btn btn-sm btn-outline-info">
                        <i class="la la-eye"></i> Récentes seulement
                    </button>
                @else
                    <button wire:click="showAllNotifications" class="btn btn-sm btn-outline-info">
                        <i class="la la-list"></i> Voir toutes les notifications
                    </button>
                @endif
            </div>
        </li>
    </ul>
</li>

@push('styles')
<style>
.notification-item {
    padding: 12px 20px;
    border-bottom: 1px solid #f1f1f1;
    transition: all 0.3s ease;
    position: relative;
}

.notification-item:hover {
    background-color: #f8f9fa;
    transform: translateX(2px);
}

.unread-notification {
    background: linear-gradient(90deg, rgba(116, 103, 239, 0.05) 0%, rgba(255, 255, 255, 0) 100%);
    border-left: 3px solid #7467ef;
}

.notification-icon-wrapper {
    position: relative;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(116, 103, 239, 0.1);
}

.notification-icon {
    font-size: 18px;
    color: #7467ef;
}

.notification-badge {
    position: absolute;
    top: -2px;
    right: -2px;
    width: 12px;
    height: 12px;
    background: #ff6b6b;
    border: 2px solid white;
    border-radius: 50%;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { transform: scale(1); opacity: 1; }
    50% { transform: scale(1.1); opacity: 0.7; }
    100% { transform: scale(1); opacity: 1; }
}

.notification-actions {
    opacity: 0;
    transition: opacity 0.3s ease;
}

.notification-item:hover .notification-actions {
    opacity: 1;
}

.notification-close:hover {
    color: #ff6b6b !important;
    transform: scale(1.1);
}

.notification-meta {
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

.notification-text {
    font-size: 13px;
    line-height: 1.4;
    color: #6c757d;
}

.empty-notifications {
    padding: 40px 20px;
}

.empty-notification-icon {
    opacity: 0.5;
}

.dropdown-menu-media {
    width: 350px;
    max-width: 350px;
}

.badge-light-primary {
    background-color: rgba(116, 103, 239, 0.1);
    color: #7467ef;
    font-size: 11px;
    padding: 4px 8px;
}

.scrollable-container::-webkit-scrollbar {
    width: 4px;
}

.scrollable-container::-webkit-scrollbar-track {
    background: #f1f1f1;
}

.scrollable-container::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 2px;
}

.scrollable-container::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}

.dropdown-menu-header {
    padding: 15px 20px;
    border-bottom: 1px solid #f1f1f1;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.dropdown-menu-header .dropdown-menu-title {
    color: white;
    font-weight: 600;
    margin-bottom: 0;
}

.dropdown-menu-header .dropdown-menu-title-text {
    color: rgba(255, 255, 255, 0.8);
}

.dropdown-menu-footer {
    padding: 15px 20px;
    border-top: 1px solid #f1f1f1;
    background-color: #fafafa;
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('livewire:init', () => {
    setInterval(() => {
        @this.call('refreshNotifications');
    }, 300000); // 5 minutes
    
    // Animation au clic sur notification
    document.addEventListener('click', function(e) {
        if (e.target.closest('.notification-item')) {
            const item = e.target.closest('.notification-item');
            item.style.transform = 'scale(0.98)';
            setTimeout(() => {
                item.style.transform = '';
            }, 150);
        }
    });
});
</script>
@endpush