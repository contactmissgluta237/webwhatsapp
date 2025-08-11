import { Logger } from './Logger.js';

/**
 * Gestionnaire de tentatives avec backoff exponentiel
 */
export class RetryHandler {
    constructor(maxRetries = 3, baseDelay = 1000) {
        this.maxRetries = maxRetries;
        this.baseDelay = baseDelay;
        this.logger = new Logger('RetryHandler');
    }

    async execute(operation, context = '') {
        let lastError;
        
        for (let attempt = 1; attempt <= this.maxRetries; attempt++) {
            try {
                return await operation();
            } catch (error) {
                lastError = error;
                
                if (attempt === this.maxRetries) {
                    this.logger.error(`Échec définitif après ${this.maxRetries} tentatives${context ? ` (${context})` : ''}:`, error.message);
                    break;
                }
                
                const delay = this.baseDelay * Math.pow(2, attempt - 1);
                this.logger.warn(`Tentative ${attempt}/${this.maxRetries} échouée${context ? ` (${context})` : ''}, retry dans ${delay}ms:`, error.message);
                
                await this._delay(delay);
            }
        }
        
        throw lastError;
    }

    _delay(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }
}