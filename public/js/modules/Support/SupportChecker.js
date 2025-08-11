import { Logger } from '../Utils/Logger.js';

/**
 * Vérificateur de support des notifications push
 * Responsabilité unique : détection des capacités du navigateur
 */
export class SupportChecker {
    constructor() {
        this.logger = new Logger('SupportChecker');
        this.cachedResult = null;
    }

    checkSupport() {
        if (this.cachedResult) {
            return this.cachedResult;
        }

        this.cachedResult = this._performSupportCheck();
        return this.cachedResult;
    }

    _performSupportCheck() {
        const userAgent = navigator.userAgent;
        const deviceInfo = this._getDeviceInfo(userAgent);
        
        const checks = [
            () => this._checkIOSSupport(deviceInfo),
            () => this._checkBasicAPISupport(),
            () => this._checkSecureContext(deviceInfo),
            () => this._checkPermissionStatus(deviceInfo),
            () => this._checkChromeSpecific(deviceInfo)
        ];

        for (const check of checks) {
            const result = check();
            if (!result.isSupported) {
                return result;
            }
        }

        return {
            isSupported: true,
            reason: 'Support complet détecté',
            ...deviceInfo
        };
    }

    _getDeviceInfo(userAgent) {
        return {
            isMobile: /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(userAgent),
            isIOS: /iPad|iPhone|iPod/.test(userAgent),
            isChrome: /Chrome/.test(userAgent),
            isChromeMobile: /Chrome/.test(userAgent) && /Android|Mobile/i.test(userAgent)
        };
    }

    _checkIOSSupport(deviceInfo) {
        if (deviceInfo.isIOS) {
            return {
                isSupported: false,
                reason: 'iOS ne supporte pas les notifications web push',
                suggestion: 'Utilisez l\'application mobile native',
                ...deviceInfo
            };
        }
        return { isSupported: true };
    }

    _checkBasicAPISupport() {
        const apis = [
            { name: 'Notification', check: () => 'Notification' in window },
            { name: 'ServiceWorker', check: () => 'serviceWorker' in navigator },
            { name: 'PushManager', check: () => 'PushManager' in window }
        ];

        const missing = apis.find(api => !api.check());
        if (missing) {
            return {
                isSupported: false,
                reason: `API ${missing.name} manquante`,
                suggestion: 'Mettez à jour votre navigateur'
            };
        }

        return { isSupported: true };
    }

    _checkSecureContext(deviceInfo) {
        const isSecure = window.isSecureContext || 
                        location.protocol === 'https:' || 
                        ['localhost', '127.0.0.1'].includes(location.hostname) ||
                        location.hostname.includes('.local');

        if (deviceInfo.isChromeMobile && !isSecure) {
            return {
                isSupported: false,
                reason: 'Chrome mobile nécessite un contexte sécurisé (HTTPS)',
                suggestion: 'Utilisez HTTPS ou un tunnel sécurisé',
                ...deviceInfo
            };
        }

        return { isSupported: true };
    }

    _checkPermissionStatus(deviceInfo) {
        if (Notification.permission === 'denied') {
            return {
                isSupported: false,
                reason: 'Notifications bloquées',
                suggestion: 'Autorisez les notifications dans les paramètres',
                ...deviceInfo
            };
        }

        return { isSupported: true };
    }

    _checkChromeSpecific(deviceInfo) {
        if (deviceInfo.isChromeMobile) {
            try {
                if (!navigator.serviceWorker.ready) {
                    return {
                        isSupported: false,
                        reason: 'Service Worker non prêt sur Chrome mobile',
                        suggestion: 'Rechargez la page',
                        ...deviceInfo
                    };
                }
            } catch (error) {
                return {
                    isSupported: false,
                    reason: 'Erreur d\'accès aux services sur Chrome mobile',
                    suggestion: 'Redémarrez le navigateur',
                    ...deviceInfo
                };
            }
        }

        return { isSupported: true };
    }

    isSupported() {
        return this.checkSupport().isSupported;
    }

    getSupportInfo() {
        return this.checkSupport();
    }
}