export class PWAInstallBanner {
    constructor() {
        this.banner = null;
        this.deferredPrompt = null;
        this.isVisible = false;
    }

    init() {
        this.createBanner();
        this.setupEventListeners();
    }

    createBanner() {
        this.banner = document.createElement('div');
        this.banner.className = 'pwa-install-banner';
        this.banner.innerHTML = `
            <div class="pwa-banner-content">
                <div class="pwa-banner-icon">
                    <img src="/images/icons/icon-72x72.png" alt="App Icon" width="48" height="48">
                </div>
                <div class="pwa-banner-text">
                    <h4>Installer l'application</h4>
                    <p>Ajoutez cette app à votre écran d'accueil pour un accès rapide</p>
                </div>
                <div class="pwa-banner-actions">
                    <button class="pwa-install-btn">Installer</button>
                    <button class="pwa-dismiss-btn">Plus tard</button>
                </div>
            </div>
        `;

        this.injectStyles();
        document.body.appendChild(this.banner);
    }

    injectStyles() {
        if (document.getElementById('pwa-banner-styles')) return;

        const styles = document.createElement('style');
        styles.id = 'pwa-banner-styles';
        styles.textContent = `
            .pwa-install-banner {
                position: fixed;
                bottom: -100px;
                left: 50%;
                transform: translateX(-50%);
                background: white;
                border-radius: 12px;
                box-shadow: 0 8px 32px rgba(0,0,0,0.12);
                border: 1px solid #e9ecef;
                padding: 16px;
                max-width: 400px;
                width: calc(100vw - 32px);
                z-index: 9999;
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                opacity: 0;
            }

            .pwa-install-banner.show {
                bottom: 20px;
                opacity: 1;
            }

            .pwa-banner-content {
                display: flex;
                align-items: center;
                gap: 12px;
            }

            .pwa-banner-icon img {
                border-radius: 8px;
            }

            .pwa-banner-text {
                flex: 1;
                min-width: 0;
            }

            .pwa-banner-text h4 {
                margin: 0 0 4px 0;
                font-size: 14px;
                font-weight: 600;
                color: #212529;
            }

            .pwa-banner-text p {
                margin: 0;
                font-size: 12px;
                color: #6c757d;
                line-height: 1.4;
            }

            .pwa-banner-actions {
                display: flex;
                flex-direction: column;
                gap: 8px;
                min-width: 80px;
            }

            .pwa-install-btn, .pwa-dismiss-btn {
                padding: 8px 16px;
                border: none;
                border-radius: 6px;
                font-size: 12px;
                font-weight: 500;
                cursor: pointer;
                transition: all 0.2s ease;
            }

            .pwa-install-btn {
                background: #0d6efd;
                color: white;
            }

            .pwa-install-btn:hover {
                background: #0b5ed7;
                transform: translateY(-1px);
            }

            .pwa-dismiss-btn {
                background: transparent;
                color: #6c757d;
                border: 1px solid #dee2e6;
            }

            .pwa-dismiss-btn:hover {
                background: #f8f9fa;
                color: #495057;
            }

            @media (max-width: 576px) {
                .pwa-install-banner {
                    bottom: -120px;
                    left: 16px;
                    right: 16px;
                    transform: none;
                    max-width: none;
                    width: auto;
                }

                .pwa-banner-content {
                    flex-direction: column;
                    text-align: center;
                    gap: 16px;
                }

                .pwa-banner-actions {
                    flex-direction: row;
                    justify-content: center;
                    width: 100%;
                }

                .pwa-install-btn, .pwa-dismiss-btn {
                    flex: 1;
                    max-width: 120px;
                }
            }

            .pwa-status-indicator {
                position: fixed;
                top: 20px;
                right: 20px;
                background: rgba(13, 110, 253, 0.9);
                color: white;
                padding: 8px 12px;
                border-radius: 20px;
                font-size: 12px;
                z-index: 1000;
                opacity: 0;
                transform: translateY(-20px);
                transition: all 0.3s ease;
            }

            .pwa-status-indicator.show {
                opacity: 1;
                transform: translateY(0);
            }

            .pwa-status-indicator.success {
                background: rgba(40, 167, 69, 0.9);
            }

            .pwa-status-indicator.offline {
                background: rgba(220, 53, 69, 0.9);
                animation: pulse 2s infinite;
            }

            @keyframes pulse {
                0%, 100% { opacity: 0.9; }
                50% { opacity: 0.6; }
            }
        `;

        document.head.appendChild(styles);
    }

    setupEventListeners() {
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            this.deferredPrompt = e;
            this.show();
        });

        window.addEventListener('appinstalled', () => {
            this.showStatus('Application installée !', 'success');
            this.hide();
        });

        this.banner.querySelector('.pwa-install-btn').addEventListener('click', () => {
            this.install();
        });

        this.banner.querySelector('.pwa-dismiss-btn').addEventListener('click', () => {
            this.dismiss();
        });
    }

    show() {
        if (this.isVisible || !this.deferredPrompt) return;

        setTimeout(() => {
            this.banner.classList.add('show');
            this.isVisible = true;
        }, 2000);
    }

    hide() {
        this.banner.classList.remove('show');
        this.isVisible = false;
    }

    async install() {
        if (!this.deferredPrompt) return;

        this.deferredPrompt.prompt();
        const { outcome } = await this.deferredPrompt.userChoice;
        
        if (outcome === 'accepted') {
            this.showStatus('Installation en cours...', 'success');
        } else {
            this.showStatus('Installation annulée', 'error');
        }

        this.deferredPrompt = null;
        this.hide();
    }

    dismiss() {
        this.hide();
        localStorage.setItem('pwa-install-dismissed', Date.now());
    }

    showStatus(message, type = 'info') {
        let indicator = document.querySelector('.pwa-status-indicator');
        
        if (!indicator) {
            indicator = document.createElement('div');
            indicator.className = 'pwa-status-indicator';
            document.body.appendChild(indicator);
        }

        indicator.textContent = message;
        indicator.className = `pwa-status-indicator ${type} show`;

        setTimeout(() => {
            indicator.classList.remove('show');
        }, 3000);
    }

    shouldShow() {
        const dismissed = localStorage.getItem('pwa-install-dismissed');
        if (!dismissed) return true;

        const dismissTime = parseInt(dismissed);
        const daysPassed = (Date.now() - dismissTime) / (1000 * 60 * 60 * 24);
        
        return daysPassed > 7;
    }
}