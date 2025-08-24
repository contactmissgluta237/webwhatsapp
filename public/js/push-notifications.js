// resources/js/push-notifications.js

class PushNotificationManager {
    constructor() {
        this.vapidPublicKey = document.querySelector('meta[name="vapid-public-key"]')?.getAttribute('content');
        this.userId = document.querySelector('meta[name="user-id"]')?.getAttribute('content');
        this.registration = null;
        this.subscription = null;
        this.init();
    }

    async init() {
        try {
            console.log('🔍 Initialisation du gestionnaire de notifications push...');
            console.log('📱 User Agent:', navigator.userAgent);
            console.log('🌐 HTTPS:', location.protocol === 'https:');
            console.log('🔧 Service Worker support:', 'serviceWorker' in navigator);
            console.log('🔔 Notification support:', 'Notification' in window);
            console.log('📲 PushManager support:', 'PushManager' in window);

            if (!this.isSupported()) {
                const reason = this.getUnsupportedReason();
                console.warn('❌ Push notifications non supportées:', reason);
                this.showUnsupportedMessage(reason);
                return;
            }

            console.log('✅ Support complet détecté');
            await this.registerServiceWorker();
            await this.checkExistingSubscription();
            
            window.pushManager = {
                subscribe: () => this.subscribe(),
                unsubscribe: () => this.unsubscribe(),
                getSubscriptionStatus: () => this.getSubscriptionStatus(),
                sendTestNotification: () => this.sendTestNotification(),
                isSupported: () => this.isSupported(),
                getSupportInfo: () => ({
                    isSupported: this.isSupported(),
                    reason: this.isSupported() ? null : this.getUnsupportedReason(),
                    suggestion: this.isSupported() ? null : 'Utilisez un navigateur récent avec HTTPS'
                })
            };
            
            console.log('✅ Push notification manager initialisé avec succès');
        } catch (error) {
            console.error('❌ Erreur lors de l\'initialisation des notifications push:', error);
        }
    }

    isSupported() {
        const hasNotification = 'Notification' in window;
        const hasServiceWorker = 'serviceWorker' in navigator;
        const hasPushManager = 'PushManager' in window;
        const isSecure = location.protocol === 'https:' || location.hostname === 'localhost';
        
        return hasNotification && hasServiceWorker && hasPushManager && isSecure;
    }

    getUnsupportedReason() {
        const checks = [
            { condition: 'Notification' in window, message: 'API Notification manquante' },
            { condition: 'serviceWorker' in navigator, message: 'Service Workers non supportés' },
            { condition: 'PushManager' in window, message: 'PushManager non disponible' },
            { condition: location.protocol === 'https:' || location.hostname === 'localhost', message: 'Contexte HTTPS requis' }
        ];

        const failed = checks.find(check => !check.condition);
        return failed ? failed.message : 'Raison inconnue';
    }

    showUnsupportedMessage(reason) {
        const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
        const isChromeMobile = /Chrome/.test(navigator.userAgent) && isMobile;
        
        let message = `Notifications push non supportées: ${reason}`;
        
        if (isChromeMobile && location.protocol !== 'https:') {
            message = '🔒 Chrome mobile nécessite HTTPS pour les notifications push. Veuillez utiliser une connexion sécurisée.';
        } else if (isMobile) {
            message = '📱 Assurez-vous d\'utiliser un navigateur récent et une connexion HTTPS.';
        }
        
        console.warn(message);
    }

    checkSupport() {
        return 'serviceWorker' in navigator && 
               'PushManager' in window && 
               'Notification' in window;
    }

    async registerServiceWorker() {
        try {
            this.registration = await navigator.serviceWorker.register('/sw.js');
            console.log('Service Worker enregistré:', this.registration);
            
            // Attendre que le SW soit prêt
            await navigator.serviceWorker.ready;
            console.log('Service Worker prêt');
            
        } catch (error) {
            console.error('Erreur lors de l\'enregistrement du Service Worker:', error);
            throw error;
        }
    }

    async requestPermission() {
        if (!('Notification' in window)) {
            throw new Error('Ce navigateur ne supporte pas les notifications');
        }

        if (Notification.permission === 'granted') {
            return true;
        }

        if (Notification.permission === 'denied') {
            throw new Error('Les notifications ont été refusées');
        }

        const permission = await Notification.requestPermission();
        
        if (permission === 'granted') {
            console.log('Permission accordée pour les notifications');
            return true;
        } else {
            throw new Error('Permission refusée pour les notifications');
        }
    }

    async subscribe() {
        try {
            if (!this.registration) {
                throw new Error('Service Worker non enregistré');
            }

            // Demander la permission
            await this.requestPermission();

            // S'abonner aux notifications push
            this.subscription = await this.registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: this.urlBase64ToUint8Array(this.vapidPublicKey)
            });

            console.log('Abonnement push créé:', this.subscription);

            // Envoyer l'abonnement au serveur
            await this.sendSubscriptionToServer(this.subscription);
            
            return this.subscription;

        } catch (error) {
            console.error('Erreur lors de l\'abonnement aux notifications push:', error);
            throw error;
        }
    }

    async sendSubscriptionToServer(subscription) {
        const response = await fetch('/push/subscribe', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                endpoint: subscription.endpoint,
                keys: {
                    p256dh: btoa(String.fromCharCode(...new Uint8Array(subscription.getKey('p256dh')))),
                    auth: btoa(String.fromCharCode(...new Uint8Array(subscription.getKey('auth'))))
                }
            })
        });

        const data = await response.json();
        
        // Vérifier si la réponse HTTP est OK ET si le data indique un succès
        if (!response.ok && !data.success) {
            throw new Error(data.message || 'Erreur lors de l\'enregistrement de l\'abonnement');
        }
        
        // Si la réponse HTTP est OK, considérer comme un succès même si data.success est false
        if (response.ok) {
            console.log('Abonnement enregistré avec succès:', data.message);
        }

        console.log('Abonnement envoyé au serveur avec succès');
        return data;
    }

    async unsubscribe() {
        try {
            if (this.subscription) {
                await this.subscription.unsubscribe();
                
                // Informer le serveur
                await fetch('/push/unsubscribe', {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        endpoint: this.subscription.endpoint
                    })
                });

                this.subscription = null;
                console.log('Désabonnement réussi');
            }
        } catch (error) {
            console.error('Erreur lors du désabonnement:', error);
            throw error;
        }
    }

    async checkExistingSubscription() {
        if (!this.registration) return;
        
        try {
            this.subscription = await this.registration.pushManager.getSubscription();
            if (this.subscription) {
                console.log('Abonnement existant trouvé:', this.subscription.endpoint);
            }
        } catch (error) {
            console.error('Erreur lors de la vérification de l\'abonnement existant:', error);
        }
    }

    // Gestion de la présence utilisateur
    startHeartbeat() {
        this.sendHeartbeat(); // Premier heartbeat immédiat
        
        this.heartbeatInterval = setInterval(() => {
            if (this.isOnline && !document.hidden) {
                this.sendHeartbeat();
            }
        }, 30000); // Toutes les 30 secondes
    }

    async sendHeartbeat() {
        try {
            const response = await fetch('/user/heartbeat', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                }
            });

            const data = await response.json();
            if (data.success) {
                console.log('Heartbeat envoyé:', data.timestamp);
            }
        } catch (error) {
            console.error('Erreur lors de l\'envoi du heartbeat:', error);
        }
    }

    async markOffline() {
        try {
            await fetch('/user/offline', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                }
            });
        } catch (error) {
            console.error('Erreur lors du marquage hors ligne:', error);
        }
    }

    setupConnectionListeners() {
        window.addEventListener('online', () => {
            console.log('Connexion rétablie');
            this.isOnline = true;
            this.sendHeartbeat();
        });

        window.addEventListener('offline', () => {
            console.log('Connexion perdue');
            this.isOnline = false;
        });

        // Gérer la visibilité de la page
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                // Page cachée, réduire l'activité
                console.log('Page cachée');
            } else {
                // Page visible, reprendre l'activité
                console.log('Page visible');
                if (this.isOnline) {
                    this.sendHeartbeat();
                }
            }
        });
    }

    setupPageUnloadListeners() {
        // Marquer comme hors ligne quand l'utilisateur quitte
        window.addEventListener('beforeunload', () => {
            // Utiliser sendBeacon pour un envoi fiable
            if (navigator.sendBeacon && this.isOnline) {
                const formData = new FormData();
                formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
                
                navigator.sendBeacon('/user/offline', formData);
            }
        });

        // Pagehide est plus fiable que beforeunload sur mobile
        window.addEventListener('pagehide', () => {
            if (navigator.sendBeacon && this.isOnline) {
                const formData = new FormData();
                formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
                
                navigator.sendBeacon('/user/offline', formData);
            }
        });
    }

    // Utilitaire pour convertir la clé VAPID
    urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - base64String.length % 4) % 4);
        const base64 = (base64String + padding)
            .replace(/\-/g, '+')
            .replace(/_/g, '/');

        const rawData = window.atob(base64);
        const outputArray = new Uint8Array(rawData.length);

        for (let i = 0; i < rawData.length; ++i) {
            outputArray[i] = rawData.charCodeAt(i);
        }
        return outputArray;
    }

    // Méthodes publiques pour l'interface utilisateur
    async getSubscriptionStatus() {
        if (!this.registration) return false;
        
        const subscription = await this.registration.pushManager.getSubscription();
        return !!subscription;
    }

    async sendTestNotification() {
        try {
            const response = await fetch('/push/test', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                }
            });

            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.message || 'Erreur lors de l\'envoi de la notification de test');
            }

            return data;
        } catch (error) {
            console.error('Erreur lors de l\'envoi de la notification de test:', error);
            throw error;
        }
    }

    stopHeartbeat() {
        if (this.heartbeatInterval) {
            clearInterval(this.heartbeatInterval);
            this.heartbeatInterval = null;
        }
    }
}

// Initialiser automatiquement si l'utilisateur est connecté
document.addEventListener('DOMContentLoaded', () => {
    // Vérifier si l'utilisateur est connecté (tu peux adapter cette vérification)
    const userMeta = document.querySelector('meta[name="user-id"]');
    if (userMeta && userMeta.getAttribute('content')) {
        window.pushManager = new PushNotificationManager();
    }
});

// Exporter pour utilisation globale
window.PushNotificationManager = PushNotificationManager;