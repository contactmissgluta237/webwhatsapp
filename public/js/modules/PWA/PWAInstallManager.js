import { Logger } from '../Utils/Logger.js';

/**
 * Gestionnaire d'installation PWA
 * Responsabilité : Détecter et proposer l'installation de l'app
 */
export class PWAInstallManager {
    constructor(configManager) {
        this.logger = new Logger('PWAInstallManager');
        this.config = configManager;
        this.deferredPrompt = null;
        this.isInstallable = false;
        this.isInstalled = false;
        this.installButton = null;
        this.dismissedCount = 0;
        this.maxDismissals = 3;
    }

    async init() {
        try {
            this.checkIfAlreadyInstalled();
            this.setupEventListeners();
            this.checkDismissalHistory();
            this.logger.success('PWA Install Manager initialisé');
        } catch (error) {
            this.logger.error('Échec initialisation PWA Install Manager:', error);
        }
    }

    checkIfAlreadyInstalled() {
        // Détection PWA installée
        this.isInstalled = window.matchMedia('(display-mode: standalone)').matches ||
                          window.navigator.standalone === true ||
                          document.referrer.includes('android-app://');
        
        if (this.isInstalled) {
            this.logger.info('PWA déjà installée');
            return;
        }
    }

    setupEventListeners() {
        // Écouter l'événement beforeinstallprompt
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            this.deferredPrompt = e;
            this.isInstallable = true;
            this.logger.info('PWA installable détectée');
            
            if (this.shouldShowInstallPrompt()) {
                this.showInstallPrompt();
            }
        });

        // Écouter l'installation réussie
        window.addEventListener('appinstalled', () => {
            this.isInstalled = true;
            this.hideInstallPrompt();
            this.clearDismissalHistory();
            this.logger.success('PWA installée avec succès');
        });
    }

    shouldShowInstallPrompt() {
        if (this.isInstalled || !this.isInstallable) {
            return false;
        }

        // Vérifier le nombre de rejets
        const dismissals = this.getDismissalCount();
        if (dismissals >= this.maxDismissals) {
            this.logger.info('Prompt d\'installation masqué - trop de rejets');
            return false;
        }

        // Vérifier si l'utilisateur a récemment rejeté
        const lastDismissal = localStorage.getItem('pwa_last_dismissal');
        if (lastDismissal) {
            const daysSinceLastDismissal = (Date.now() - parseInt(lastDismissal)) / (1000 * 60 * 60 * 24);
            if (daysSinceLastDismissal < 7) { // Attendre 7 jours
                return false;
            }
        }

        return true;
    }

    showInstallPrompt() {
        if (this.installButton) {
            return; // Déjà affiché
        }

        this.createInstallButton();
        this.animateInstallPrompt();
    }

    createInstallButton() {
        // Créer le conteneur du prompt
        const promptContainer = document.createElement('div');
        promptContainer.id = 'pwa-install-prompt';
        promptContainer.className = 'pwa-install-prompt';
        promptContainer.innerHTML = `
            <div class="pwa-prompt-content">
                <div class="pwa-prompt-icon">
                    <i class="ti ti-download"></i>
                </div>
                <div class="pwa-prompt-text">
                    <h4>Installer l'application</h4>
                    <p>Ajoutez cette app à votre écran d'accueil pour un accès rapide et une meilleure expérience</p>
                </div>
                <div class="pwa-prompt-actions">
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="pwa-dismiss">
                        Plus tard
                    </button>
                    <button type="button" class="btn btn-primary btn-sm" id="pwa-install">
                        <i class="ti ti-download me-1"></i>
                        Installer
                    </button>
                </div>
                <button type="button" class="btn-close pwa-close" id="pwa-close"></button>
            </div>
        `;

        // Ajouter les styles
        this.addInstallPromptStyles();

        // Ajouter au DOM
        document.body.appendChild(promptContainer);
        this.installButton = promptContainer;

        // Ajouter les événements
        this.attachPromptEventListeners();
    }

    addInstallPromptStyles() {
        if (document.getElementById('pwa-install-styles')) {
            return;
        }

        const styles = document.createElement('style');
        styles.id = 'pwa-install-styles';
        styles.textContent = `
            .pwa-install-prompt {
                position: fixed;
                bottom: 20px;
                left: 50%;
                transform: translateX(-50%) translateY(100%);
                background: white;
                border-radius: 12px;
                box-shadow: 0 8px 32px rgba(0,0,0,0.15);
                border: 1px solid rgba(0,0,0,0.1);
                max-width: 400px;
                width: calc(100% - 40px);
                z-index: 9999;
                transition: transform 0.3s ease-out;
            }
            
            .pwa-install-prompt.show {
                transform: translateX(-50%) translateY(0);
            }
            
            .pwa-prompt-content {
                padding: 20px;
                position: relative;
            }
            
            .pwa-prompt-icon {
                text-align: center;
                margin-bottom: 12px;
            }
            
            .pwa-prompt-icon i {
                font-size: 2rem;
                color: var(--bs-primary);
            }
            
            .pwa-prompt-text h4 {
                margin: 0 0 8px 0;
                font-size: 1.1rem;
                font-weight: 600;
                text-align: center;
            }
            
            .pwa-prompt-text p {
                margin: 0 0 16px 0;
                font-size: 0.9rem;
                color: #666;
                text-align: center;
                line-height: 1.4;
            }
            
            .pwa-prompt-actions {
                display: flex;
                gap: 8px;
                justify-content: center;
            }
            
            .pwa-close {
                position: absolute;
                top: 8px;
                right: 8px;
                background: none;
                border: none;
                font-size: 1.2rem;
                cursor: pointer;
                opacity: 0.6;
            }
            
            .pwa-close:hover {
                opacity: 1;
            }
            
            @media (max-width: 480px) {
                .pwa-install-prompt {
                    bottom: 10px;
                    width: calc(100% - 20px);
                }
                
                .pwa-prompt-content {
                    padding: 16px;
                }
                
                .pwa-prompt-actions {
                    flex-direction: column;
                }
            }
        `;
        
        document.head.appendChild(styles);
    }

    attachPromptEventListeners() {
        const installBtn = document.getElementById('pwa-install');
        const dismissBtn = document.getElementById('pwa-dismiss');
        const closeBtn = document.getElementById('pwa-close');

        installBtn?.addEventListener('click', () => this.handleInstallClick());
        dismissBtn?.addEventListener('click', () => this.handleDismissClick());
        closeBtn?.addEventListener('click', () => this.handleCloseClick());
    }

    animateInstallPrompt() {
        setTimeout(() => {
            this.installButton?.classList.add('show');
        }, 100);
    }

    async handleInstallClick() {
        if (!this.deferredPrompt) {
            this.logger.error('Pas de prompt d\'installation disponible');
            return;
        }

        try {
            const result = await this.deferredPrompt.prompt();
            this.logger.info('Résultat installation:', result.outcome);

            if (result.outcome === 'accepted') {
                this.logger.success('Installation acceptée');
            } else {
                this.logger.info('Installation refusée');
                this.incrementDismissalCount();
            }

            this.deferredPrompt = null;
            this.hideInstallPrompt();
        } catch (error) {
            this.logger.error('Erreur lors de l\'installation:', error);
        }
    }

    handleDismissClick() {
        this.incrementDismissalCount();
        this.hideInstallPrompt();
        this.logger.info('Installation reportée');
    }

    handleCloseClick() {
        this.incrementDismissalCount();
        this.hideInstallPrompt();
        this.logger.info('Prompt fermé');
    }

    hideInstallPrompt() {
        if (!this.installButton) return;

        this.installButton.classList.remove('show');
        setTimeout(() => {
            this.installButton?.remove();
            this.installButton = null;
        }, 300);
    }

    getDismissalCount() {
        return parseInt(localStorage.getItem('pwa_dismissal_count') || '0');
    }

    incrementDismissalCount() {
        const count = this.getDismissalCount() + 1;
        localStorage.setItem('pwa_dismissal_count', count.toString());
        localStorage.setItem('pwa_last_dismissal', Date.now().toString());
    }

    clearDismissalHistory() {
        localStorage.removeItem('pwa_dismissal_count');
        localStorage.removeItem('pwa_last_dismissal');
    }

    checkDismissalHistory() {
        this.dismissedCount = this.getDismissalCount();
        if (this.dismissedCount >= this.maxDismissals) {
            this.logger.info('Installation PWA définitivement masquée');
        }
    }

    // API publique pour forcer l'affichage du prompt
    forceShowInstallPrompt() {
        if (this.isInstallable && !this.isInstalled) {
            this.showInstallPrompt();
        }
    }

    // API publique pour vérifier si l'installation est possible
    canInstall() {
        return this.isInstallable && !this.isInstalled;
    }

    // API publique pour vérifier si l'app est installée
    isAppInstalled() {
        return this.isInstalled;
    }

    destroy() {
        this.hideInstallPrompt();
        const styles = document.getElementById('pwa-install-styles');
        styles?.remove();
        this.logger.info('PWA Install Manager détruit');
    }
}