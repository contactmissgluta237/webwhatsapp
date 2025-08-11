import { ServiceWorkerManager } from '../ServiceWorker/ServiceWorkerManager.js';
import { SubscriptionManager } from '../Subscription/SubscriptionManager.js';
import { SupportChecker } from '../Support/SupportChecker.js';
import { ConnectionManager } from '../Connection/ConnectionManager.js';
import { NotificationAPI } from '../API/NotificationAPI.js';
import { ConfigManager } from '../Config/ConfigManager.js';
import { Logger } from '../Utils/Logger.js';

/**
 * Gestionnaire principal des notifications push
 * Respecte le principe de responsabilité unique (SRP)
 */
export class PushNotificationManager {
    constructor(config = {}) {
        this.logger = new Logger('PushNotificationManager');
        this.configManager = new ConfigManager(config);
        this.supportChecker = new SupportChecker();
        this.serviceWorkerManager = new ServiceWorkerManager(this.configManager);
        this.subscriptionManager = new SubscriptionManager(this.configManager, this.serviceWorkerManager);
        this.connectionManager = new ConnectionManager(this.configManager);
        this.api = new NotificationAPI(this.configManager);
        
        this.isInitialized = false;
        this.initPromise = null;
    }

    async init() {
        if (this.isInitialized) {
            return true;
        }

        if (this.initPromise) {
            return this.initPromise;
        }

        this.initPromise = this._performInit();
        return this.initPromise;
    }

    async _performInit() {
        try {
            this.logger.info('Initialisation du gestionnaire de notifications push...');
            
            const supportResult = this.supportChecker.checkSupport();
            if (!supportResult.isSupported) {
                this.logger.warn('Push notifications non supportées:', supportResult.reason);
                this._handleUnsupported(supportResult);
                return false;
            }

            await this.serviceWorkerManager.init();
            await this.subscriptionManager.init();
            this.connectionManager.init();

            this._exposeGlobalAPI();
            
            this.isInitialized = true;
            this.logger.success('Push notification manager initialisé avec succès');
            return true;

        } catch (error) {
            this.logger.error('Erreur lors de l\'initialisation:', error);
            this.isInitialized = false;
            throw error;
        }
    }

    _handleUnsupported(supportResult) {
        const event = new CustomEvent('pushNotificationUnsupported', {
            detail: supportResult
        });
        window.dispatchEvent(event);
    }

    _exposeGlobalAPI() {
        window.pushManager = {
            subscribe: () => this.subscriptionManager.subscribe(),
            unsubscribe: () => this.subscriptionManager.unsubscribe(),
            getSubscriptionStatus: () => this.subscriptionManager.getStatus(),
            sendTestNotification: () => this.api.sendTestNotification(),
            isSupported: () => this.supportChecker.isSupported(),
            getSupportInfo: () => this.supportChecker.getSupportInfo(),
            destroy: () => this.destroy()
        };
    }

    async destroy() {
        this.connectionManager.destroy();
        this.serviceWorkerManager.destroy();
        this.subscriptionManager.destroy();
        this.isInitialized = false;
        this.initPromise = null;
    }
}