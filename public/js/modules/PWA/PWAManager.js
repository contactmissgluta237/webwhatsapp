import { Logger } from '../Utils/Logger.js';
import { PWAInstallManager } from './PWAInstallManager.js';
import { PWAInstallBanner } from './PWAInstallBanner.js';

/**
 * Gestionnaire principal PWA
 * Responsabilité : Orchestrer tous les composants PWA
 */
export class PWAManager {
    constructor(configManager) {
        this.logger = new Logger('PWAManager');
        this.configManager = configManager;
        this.installManager = new PWAInstallManager();
        this.installBanner = new PWAInstallBanner();
        this.isInitialized = false;
    }

    async init() {
        try {
            this.logger.info('Initialisation du PWA Manager...');

            await this.installManager.init();
            this.installBanner.init();

            this.setupGlobalEventListeners();
            this.setupConnectionStatus();
            
            this.isInitialized = true;
            this.logger.info('✅ PWA Manager initialisé avec succès');

            return true;
        } catch (error) {
            this.logger.error('❌ Erreur lors de l\'initialisation PWA:', error);
            return false;
        }
    }

    setupGlobalEventListeners() {
        window.addEventListener('beforeinstallprompt', (e) => {
            this.logger.info('🚀 Événement beforeinstallprompt détecté');
            if (this.installBanner.shouldShow()) {
                this.installBanner.show();
            }
        });

        window.addEventListener('appinstalled', () => {
            this.logger.info('🎉 Application PWA installée');
            this.installBanner.showStatus('Application installée avec succès !', 'success');
        });

        window.addEventListener('online', () => {
            this.logger.info('🌐 Connexion rétablie');
            this.updateConnectionStatus(true);
        });

        window.addEventListener('offline', () => {
            this.logger.info('📴 Connexion perdue - Mode hors ligne');
            this.updateConnectionStatus(false);
        });
    }

    setupConnectionStatus() {
        let statusElement = document.getElementById('connection-status');
        if (!statusElement) {
            statusElement = document.createElement('div');
            statusElement.id = 'connection-status';
            statusElement.className = 'connection-status';
            document.body.appendChild(statusElement);
        }

        this.updateConnectionStatus(navigator.onLine);
    }

    updateConnectionStatus(isOnline) {
        const statusElement = document.getElementById('connection-status');
        if (statusElement) {
            statusElement.textContent = isOnline ? 'En ligne' : 'Hors ligne';
            statusElement.className = `connection-status ${isOnline ? 'online' : 'offline'}`;
            
            if (!isOnline) {
                this.installBanner.showStatus('Mode hors ligne activé', 'offline');
            }
        }
    }

    async forceInstall() {
        return await this.installManager.promptInstall();
    }

    // API publique pour obtenir le statut PWA
    getStatus() {
        return {
            isInitialized: this.isInitialized,
            isSupported: this.installManager.isSupported(),
            canInstall: this.installManager.canShowPrompt(),
            isInstalled: this.isInstalled(),
            isOnline: navigator.onLine
        };
    }

    isInstalled() {
        return window.matchMedia('(display-mode: standalone)').matches || 
               window.navigator.standalone === true;
    }

    async checkForUpdates() {
        if ('serviceWorker' in navigator) {
            const registration = await navigator.serviceWorker.getRegistration();
            if (registration) {
                await registration.update();
                this.logger.info('🔄 Vérification des mises à jour effectuée');
            }
        }
    }

    // Méthode pour nettoyer les ressources
    destroy() {
        this.installManager.destroy();
        this.installBanner.hide();
        this.logger.info('🧹 PWA Manager nettoyé');
    }
}