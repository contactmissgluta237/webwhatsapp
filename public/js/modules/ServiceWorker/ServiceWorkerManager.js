import { Logger } from '../Utils/Logger.js';

/**
 * Gestionnaire de Service Worker
 * Responsabilité unique : enregistrement et gestion du SW
 */
export class ServiceWorkerManager {
    constructor(configManager) {
        this.logger = new Logger('ServiceWorkerManager');
        this.config = configManager;
        this.registration = null;
        this.isReady = false;
    }

    async init() {
        try {
            await this._registerServiceWorker();
            await this._waitForReady();
            this.isReady = true;
            this.logger.success('Service Worker initialisé');
        } catch (error) {
            this.logger.error('Échec initialisation Service Worker:', error);
            throw error;
        }
    }

    async _registerServiceWorker() {
        const existingRegistration = await navigator.serviceWorker.getRegistration(
            this.config.get('serviceWorkerScope')
        );
        
        if (existingRegistration) {
            this.logger.info('Service Worker existant trouvé');
            this.registration = existingRegistration;
        } else {
            this.logger.info('Enregistrement d\'un nouveau Service Worker...');
            this.registration = await navigator.serviceWorker.register(
                this.config.get('serviceWorkerPath'),
                { scope: this.config.get('serviceWorkerScope') }
            );
            this.logger.success('Service Worker enregistré');
        }
    }

    async _waitForReady() {
        await navigator.serviceWorker.ready;
        this.logger.info('Service Worker prêt');
    }

    getRegistration() {
        if (!this.isReady) {
            throw new Error('Service Worker not ready');
        }
        return this.registration;
    }

    destroy() {
        this.registration = null;
        this.isReady = false;
    }
}