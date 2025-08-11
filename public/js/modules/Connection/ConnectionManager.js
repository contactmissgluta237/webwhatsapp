import { Logger } from '../Utils/Logger.js';

/**
 * Gestionnaire de connexion et heartbeat
 * Responsabilité unique : surveillance de l'état de connexion
 */
export class ConnectionManager {
    constructor(configManager) {
        this.logger = new Logger('ConnectionManager');
        this.config = configManager;
        this.isOnline = navigator.onLine;
        this.heartbeatInterval = null;
    }

    init() {
        this._setupConnectionListeners();
        this._setupPageUnloadListeners();
        this._startHeartbeat();
        this.logger.success('Gestionnaire de connexion initialisé');
    }

    _setupConnectionListeners() {
        window.addEventListener('online', () => {
            this.logger.info('Connexion rétablie');
            this.isOnline = true;
            this._sendHeartbeat();
        });

        window.addEventListener('offline', () => {
            this.logger.warn('Connexion perdue');
            this.isOnline = false;
        });

        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                this.logger.debug('Page cachée');
            } else {
                this.logger.debug('Page visible');
                if (this.isOnline) {
                    this._sendHeartbeat();
                }
            }
        });
    }

    _setupPageUnloadListeners() {
        const sendOfflineBeacon = () => {
            if (navigator.sendBeacon && this.isOnline) {
                const formData = new FormData();
                formData.append('_token', this.config.get('csrfToken'));
                navigator.sendBeacon(this.config.getEndpoint('offline'), formData);
            }
        };

        window.addEventListener('beforeunload', sendOfflineBeacon);
        window.addEventListener('pagehide', sendOfflineBeacon);
    }

    _startHeartbeat() {
        this._sendHeartbeat();
        
        this.heartbeatInterval = setInterval(() => {
            if (this.isOnline && !document.hidden) {
                this._sendHeartbeat();
            }
        }, this.config.get('heartbeatInterval'));
    }

    async _sendHeartbeat() {
        try {
            const response = await fetch(this.config.getEndpoint('heartbeat'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.config.get('csrfToken'),
                    'Accept': 'application/json'
                }
            });

            const data = await response.json();
            if (data.success) {
                this.logger.debug('Heartbeat envoyé:', data.timestamp);
            }
        } catch (error) {
            this.logger.error('Erreur heartbeat:', error);
        }
    }

    async _markOffline() {
        try {
            await fetch(this.config.getEndpoint('offline'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.config.get('csrfToken'),
                    'Accept': 'application/json'
                }
            });
        } catch (error) {
            this.logger.error('Erreur marquage hors ligne:', error);
        }
    }

    getConnectionStatus() {
        return this.isOnline;
    }

    destroy() {
        if (this.heartbeatInterval) {
            clearInterval(this.heartbeatInterval);
            this.heartbeatInterval = null;
        }
        this._markOffline();
    }
}