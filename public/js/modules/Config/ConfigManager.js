import { Logger } from '../Utils/Logger.js';

/**
 * Gestionnaire de configuration centralisé
 * Respecte le principe ouvert/fermé (OCP)
 */
export class ConfigManager {
    constructor(customConfig = {}) {
        this.logger = new Logger('ConfigManager');
        this.config = this._buildConfig(customConfig);
        this._validateConfig();
    }

    _buildConfig(customConfig) {
        const defaultConfig = {
            vapidPublicKey: this._getMetaContent('vapid-public-key'),
            userId: this._getMetaContent('user-id'),
            csrfToken: this._getMetaContent('csrf-token'),
            serviceWorkerPath: '/sw.js',
            serviceWorkerScope: '/',
            heartbeatInterval: 30000,
            maxRetries: 3,
            retryDelay: 1000,
            endpoints: {
                subscribe: '/push/subscribe',
                unsubscribe: '/push/unsubscribe',
                test: '/test/notification',
                heartbeat: '/user/heartbeat',
                offline: '/user/offline'
            }
        };

        return { ...defaultConfig, ...customConfig };
    }

    _getMetaContent(name) {
        return document.querySelector(`meta[name="${name}"]`)?.getAttribute('content');
    }

    _validateConfig() {
        const required = ['vapidPublicKey', 'userId', 'csrfToken'];
        const missing = required.filter(key => !this.config[key]);
        
        if (missing.length > 0) {
            throw new Error(`Configuration manquante: ${missing.join(', ')}`);
        }

        this.logger.info('Configuration validée');
    }

    get(key) {
        return this.config[key];
    }

    set(key, value) {
        this.config[key] = value;
    }

    getEndpoint(name) {
        return this.config.endpoints[name];
    }
}