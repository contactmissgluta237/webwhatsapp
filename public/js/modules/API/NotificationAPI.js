import { Logger } from '../Utils/Logger.js';

/**
 * API de communication avec le serveur
 * Responsabilité unique : requêtes vers les endpoints de notification
 */
export class NotificationAPI {
    constructor(configManager) {
        this.logger = new Logger('NotificationAPI');
        this.config = configManager;
    }

    async sendTestNotification() {
        try {
            const response = await fetch(this.config.getEndpoint('test'), {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': this.config.get('csrfToken')
                }
            });

            const data = await response.json();
            
            if (data.success) {
                this.logger.success('Test notification envoyée avec succès');
                return data;
            } else {
                throw new Error(data.message || 'Échec envoi test notification');
            }
        } catch (error) {
            this.logger.error('Test notification échouée:', error);
            throw error;
        }
    }

    async makeRequest(endpoint, options = {}) {
        const defaultOptions = {
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': this.config.get('csrfToken'),
                'Accept': 'application/json'
            }
        };

        const mergedOptions = {
            ...defaultOptions,
            ...options,
            headers: {
                ...defaultOptions.headers,
                ...options.headers
            }
        };

        try {
            const response = await fetch(endpoint, mergedOptions);
            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || `HTTP ${response.status}`);
            }

            return data;
        } catch (error) {
            this.logger.error(`Erreur requête vers ${endpoint}:`, error);
            throw error;
        }
    }
}