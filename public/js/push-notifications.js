class PushNotificationManager {
    constructor(config = {}) {
        this.config = {
            heartbeatInterval: 60000,
            maxRetries: 5,
            ...config
        };
        this.isInitialized = false;
    }

    async init() {
        try {
            if (!('serviceWorker' in navigator)) {
                console.log('Service Worker non supporté');
                return false;
            }

            if (!('PushManager' in window)) {
                console.log('Push messaging non supporté');
                return false;
            }

            const permission = await Notification.requestPermission();
            if (permission !== 'granted') {
                console.log('Permission notifications refusée');
                return false;
            }

            this.isInitialized = true;
            console.log('✅ Push notifications initialisées');
            return true;
        } catch (error) {
            console.error('❌ Erreur init push notifications:', error);
            return false;
        }
    }

    getStatus() {
        return {
            isInitialized: this.isInitialized,
            permission: Notification.permission
        };
    }
}

document.addEventListener('DOMContentLoaded', async () => {
    const userMeta = document.querySelector('meta[name="user-id"]');
    
    if (userMeta?.getAttribute('content')) {
        try {
            const pushManager = new PushNotificationManager();
            await pushManager.init();
        } catch (error) {
            console.error('Échec initialisation push notifications:', error);
        }
    }
});

window.PushNotificationManager = PushNotificationManager;