class PWAInstaller {
    constructor() {
        this.deferredPrompt = null;
        this.isInstalled = false;
        this.headerButton = null;
    }

    init() {
        this.headerButton = document.getElementById('pwa-install-header-btn');
        this.setupEventListeners();
        this.checkInstallationStatus();
    }

    setupEventListeners() {
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            this.deferredPrompt = e;
            this.showInstallButton();
            console.log('📱 PWA installation prompt available');
        });

        window.addEventListener('appinstalled', () => {
            this.hideInstallButton();
            this.showSuccessMessage();
            console.log('🎉 PWA installed successfully');
        });

        if (this.headerButton) {
            this.headerButton.addEventListener('click', (e) => {
                e.preventDefault();
                this.promptInstall();
            });
        }
    }

    checkInstallationStatus() {
        this.isInstalled = window.matchMedia('(display-mode: standalone)').matches || 
                          window.navigator.standalone === true;
        
        if (this.isInstalled) {
            this.hideInstallButton();
        }
    }

    showInstallButton() {
        if (this.headerButton && !this.isInstalled) {
            this.headerButton.style.display = 'block';
        }
    }

    hideInstallButton() {
        if (this.headerButton) {
            this.headerButton.style.display = 'none';
        }
    }

    async promptInstall() {
        if (!this.deferredPrompt) {
            console.log('No install prompt available');
            return;
        }

        this.deferredPrompt.prompt();
        const { outcome } = await this.deferredPrompt.userChoice;
        
        if (outcome === 'accepted') {
            console.log('User accepted the install prompt');
        } else {
            console.log('User dismissed the install prompt');
        }
        
        this.deferredPrompt = null;
    }

    showSuccessMessage() {
        const toast = document.createElement('div');
        toast.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: white;
            border: 1px solid #28a745;
            border-left: 4px solid #28a745;
            border-radius: 8px;
            padding: 16px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 10000;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            font-weight: 500;
            color: #155724;
            transform: translateX(100%);
            transition: transform 0.3s ease;
        `;
        
        toast.innerHTML = `
            <i class="ft-check-circle" style="color: #28a745;"></i>
            <span>Application installée avec succès !</span>
        `;
        
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.style.transform = 'translateX(0)';
        }, 100);
        
        setTimeout(() => {
            toast.style.transform = 'translateX(100%)';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const pwaInstaller = new PWAInstaller();
    pwaInstaller.init();
    
    window.PWA_INSTALLER = pwaInstaller;
});