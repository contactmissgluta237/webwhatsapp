import { Logger } from '../Utils/Logger.js';
import { RetryHandler } from '../Utils/RetryHandler.js';
import { Base64Utils } from '../Utils/Base64Utils.js';

/**
 * Gestionnaire d'abonnements push
 * Responsabilité unique : gestion du cycle de vie des abonnements
 */
export class SubscriptionManager {
    constructor(configManager, serviceWorkerManager) {
        this.logger = new Logger('SubscriptionManager');
        this.config = configManager;
        this.swManager = serviceWorkerManager;
        this.retryHandler = new RetryHandler(
            this.config.get('maxRetries'),
            this.config.get('retryDelay')
        );
        this.subscription = null;
    }

    async init() {
        await this._checkExistingSubscription();
    }

    async _checkExistingSubscription() {
        try {
            const registration = this.swManager.getRegistration();
            this.subscription = await registration.pushManager.getSubscription();
            
            const hasSubscription = !!this.subscription;
            this.logger.info(
                hasSubscription 
                ? 'Abonnement existant trouvé' 
                : 'Aucun abonnement existant'
            );
            
            return hasSubscription;
        } catch (error) {
            this.logger.error('Erreur vérification abonnement:', error);
            return false;
        }
    }

    async subscribe() {
        try {
            await this._requestPermission();
            await this._createSubscription();
            await this._saveSubscriptionToServer();
            
            this.logger.success('Abonnement créé avec succès');
            return this.subscription;
            
        } catch (error) {
            this.logger.error('Échec abonnement:', error);
            throw error;
        }
    }

    async _requestPermission() {
        if (Notification.permission === 'granted') {
            return;
        }

        this.logger.info('Demande de permission...');
        const permission = await Notification.requestPermission();
        
        if (permission !== 'granted') {
            throw new Error(
                permission === 'denied' 
                ? 'Permission refusée. Réactivez dans les paramètres.'
                : 'Permission requise pour les notifications'
            );
        }
        
        this.logger.success('Permission accordée');
    }

    async _createSubscription() {
        const registration = this.swManager.getRegistration();
        const vapidKey = Base64Utils.urlBase64ToUint8Array(
            this.config.get('vapidPublicKey')
        );

        this.subscription = await this.retryHandler.execute(async () => {
            return registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: vapidKey
            });
        });

        this.logger.success('Abonnement push créé');
    }

    async _saveSubscriptionToServer() {
        const subscriptionData = {
            endpoint: this.subscription.endpoint,
            keys: {
                p256dh: Base64Utils.arrayBufferToBase64(this.subscription.getKey('p256dh')),
                auth: Base64Utils.arrayBufferToBase64(this.subscription.getKey('auth'))
            }
        };

        const response = await fetch(this.config.getEndpoint('subscribe'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': this.config.get('csrfToken'),
                'Accept': 'application/json'
            },
            body: JSON.stringify(subscriptionData)
        });

        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.message || 'Échec sauvegarde sur serveur');
        }

        this.logger.success('Abonnement sauvegardé sur serveur');
    }

    async unsubscribe() {
        if (!this.subscription) {
            throw new Error('Aucun abonnement à supprimer');
        }

        try {
            await this.subscription.unsubscribe();
            await this._removeFromServer();
            
            this.subscription = null;
            this.logger.success('Abonnement supprimé');
            
        } catch (error) {
            this.logger.error('Échec désabonnement:', error);
            throw error;
        }
    }

    async _removeFromServer() {
        await fetch(this.config.getEndpoint('unsubscribe'), {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': this.config.get('csrfToken'),
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                endpoint: this.subscription.endpoint
            })
        });
    }

    getStatus() {
        return !!this.subscription;
    }

    getSubscription() {
        return this.subscription;
    }

    destroy() {
        this.subscription = null;
    }
}