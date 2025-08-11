@props(['position' => 'bottom-right', 'showAlways' => false])

<div id="push-notification-float" 
     class="push-notification-float position-fixed {{ $position }}" 
     style="display: none; z-index: 9999;">
    
    <div class="card shadow-lg border-0" style="max-width: 350px; min-width: 300px;">
        <div class="card-body p-3">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <div class="d-flex align-items-center">
                    <i class="ft-bell text-primary mr-2" style="font-size: 1.2rem;"></i>
                    <h6 class="mb-0 font-weight-bold">Notifications Push</h6>
                </div>
                <button type="button" class="btn btn-sm btn-link text-muted p-0" 
                        onclick="closePushFloat()" 
                        style="font-size: 1.2rem; line-height: 1;">
                    ×
                </button>
            </div>
            
            <div id="push-float-content">
                <p class="text-muted small mb-3">
                    Activez les notifications pour ne rien manquer !
                </p>
                
                <div id="push-float-status" class="mb-3">
                    <span id="push-status-text" class="small">Vérification...</span>
                </div>
                
                <div id="push-float-actions" class="d-flex gap-2">
                    <button id="enable-push-btn" 
                            class="btn btn-primary btn-sm flex-fill" 
                            onclick="activatePushNotifications()"
                            style="display: none;">
                        <i class="ft-bell mr-1"></i>
                        Activer
                    </button>
                    
                    <button id="disable-push-btn" 
                            class="btn btn-outline-secondary btn-sm flex-fill" 
                            onclick="deactivatePushNotifications()"
                            style="display: none;">
                        <i class="ft-bell-off mr-1"></i>
                        Désactiver
                    </button>
                </div>
                
                <div id="push-float-settings" style="display: none;">
                    <div class="text-center mt-2">
                        <a href="{{ auth()->user()->hasRole('admin') ? route('admin.profile.show') : route('customer.profile.show') }}" class="btn btn-link btn-sm p-0">
                            Gérer dans le profil
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.push-notification-float {
    transition: all 0.3s ease-in-out;
    opacity: 0;
    transform: translateY(20px);
}

.push-notification-float.show {
    opacity: 1;
    transform: translateY(0);
}

.push-notification-float.bottom-right {
    bottom: 20px;
    right: 20px;
}

.push-notification-float.bottom-left {
    bottom: 20px;
    left: 20px;
}

.push-notification-float.top-right {
    top: 80px;
    right: 20px;
}

.push-notification-float.top-left {
    top: 80px;
    left: 20px;
}

@media (max-width: 768px) {
    .push-notification-float {
        left: 10px !important;
        right: 10px !important;
        bottom: 10px !important;
        max-width: none !important;
    }
    
    .push-notification-float .card {
        max-width: none !important;
        min-width: auto !important;
    }
}

.push-status-success {
    color: #28a745 !important;
}

.push-status-warning {
    color: #ffc107 !important;
}

.push-status-error {
    color: #dc3545 !important;
}

.d-flex.gap-2 > * + * {
    margin-left: 0.5rem;
}
</style>

<script>
// Variables globales pour le composant flottant
let pushFloatVisible = false;
let pushFloatDismissed = false;
let pushFloatCheckInterval = null;

// Configuration
const PUSH_FLOAT_CONFIG = {
    showAlways: {{ $showAlways ? 'true' : 'false' }},
    dismissKey: 'push_float_dismissed_' + {{ auth()->id() ?? 0 }},
    checkInterval: 3000,
    autoHideDelay: 10000
};

async function activatePushNotifications() {
    const enableBtn = document.getElementById('enable-push-btn');
    const statusText = document.getElementById('push-status-text');
    
    try {
        enableBtn.disabled = true;
        enableBtn.innerHTML = '<i class="ft-clock mr-1"></i> Activation...';
        
        if (!window.pushManager) {
            throw new Error('Service de notifications non disponible');
        }
        
        await window.pushManager.subscribe();
        
        statusText.textContent = 'Notifications activées avec succès !';
        statusText.className = 'small push-status-success';
        
        updatePushFloatButtons(true);
        
        // Auto-masquer après succès
        setTimeout(() => {
            if (!PUSH_FLOAT_CONFIG.showAlways) {
                closePushFloat();
            }
        }, 3000);
        
    } catch (error) {
        console.error('Erreur activation notifications:', error);
        statusText.textContent = `Erreur: ${error.message}`;
        statusText.className = 'small push-status-error';
        
        enableBtn.disabled = false;
        enableBtn.innerHTML = '<i class="ft-bell mr-1"></i> Réessayer';
    }
}

async function deactivatePushNotifications() {
    const disableBtn = document.getElementById('disable-push-btn');
    const statusText = document.getElementById('push-status-text');
    
    try {
        disableBtn.disabled = true;
        disableBtn.innerHTML = '<i class="ft-clock mr-1"></i> Désactivation...';
        
        if (!window.pushManager) {
            throw new Error('Service de notifications non disponible');
        }
        
        await window.pushManager.unsubscribe();
        
        statusText.textContent = 'Notifications désactivées';
        statusText.className = 'small push-status-warning';
        
        updatePushFloatButtons(false);
        
    } catch (error) {
        console.error('Erreur désactivation notifications:', error);
        statusText.textContent = `Erreur: ${error.message}`;
        statusText.className = 'small push-status-error';
        
        disableBtn.disabled = false;
        disableBtn.innerHTML = '<i class="ft-bell-off mr-1"></i> Réessayer';
    }
}

function updatePushFloatButtons(isSubscribed) {
    const enableBtn = document.getElementById('enable-push-btn');
    const disableBtn = document.getElementById('disable-push-btn');
    const settingsDiv = document.getElementById('push-float-settings');
    
    if (isSubscribed) {
        enableBtn.style.display = 'none';
        disableBtn.style.display = 'block';
        disableBtn.disabled = false;
        disableBtn.innerHTML = '<i class="ft-bell-off mr-1"></i> Désactiver';
        settingsDiv.style.display = 'block';
    } else {
        enableBtn.style.display = 'block';
        enableBtn.disabled = false;
        enableBtn.innerHTML = '<i class="ft-bell mr-1"></i> Activer';
        disableBtn.style.display = 'none';
        settingsDiv.style.display = 'none';
    }
}

function showPushFloat() {
    if (pushFloatVisible || pushFloatDismissed) return;
    
    const floatElement = document.getElementById('push-notification-float');
    if (!floatElement) return;
    
    floatElement.style.display = 'block';
    
    // Animation d'apparition
    setTimeout(() => {
        floatElement.classList.add('show');
        pushFloatVisible = true;
    }, 100);
    
    // Auto-masquer si pas d'interaction
    if (!PUSH_FLOAT_CONFIG.showAlways) {
        setTimeout(() => {
            if (pushFloatVisible && !document.querySelector('#push-notification-float:hover')) {
                closePushFloat();
            }
        }, PUSH_FLOAT_CONFIG.autoHideDelay);
    }
}

function closePushFloat() {
    const floatElement = document.getElementById('push-notification-float');
    if (!floatElement) return;
    
    floatElement.classList.remove('show');
    
    setTimeout(() => {
        floatElement.style.display = 'none';
        pushFloatVisible = false;
    }, 300);
    
    // Marquer comme fermé par l'utilisateur
    pushFloatDismissed = true;
    localStorage.setItem(PUSH_FLOAT_CONFIG.dismissKey, Date.now().toString());
}

async function checkPushFloatStatus() {
    const statusText = document.getElementById('push-status-text');
    
    try {
        // Vérifier si le gestionnaire est disponible
        if (!window.pushManager) {
            statusText.textContent = 'Service en cours d\'initialisation...';
            statusText.className = 'small push-status-warning';
            return false;
        }
        
        // Vérifier le support
        if (!window.pushManager.isSupported || !window.pushManager.isSupported()) {
            statusText.textContent = 'Non supporté sur ce navigateur';
            statusText.className = 'small push-status-error';
            return false;
        }
        
        // Vérifier l'état d'abonnement
        const isSubscribed = await window.pushManager.getSubscriptionStatus();
        
        if (isSubscribed) {
            statusText.textContent = 'Notifications activées';
            statusText.className = 'small push-status-success';
            updatePushFloatButtons(true);
            
            // En développement localhost, toujours montrer pour debug
            const isLocalhost = location.hostname === 'localhost' || location.hostname === '127.0.0.1';
            return PUSH_FLOAT_CONFIG.showAlways || isLocalhost;
        } else {
            statusText.textContent = 'Activez pour recevoir les notifications';
            statusText.className = 'small';
            updatePushFloatButtons(false);
            return true; // Montrer pour permettre l'activation
        }
        
    } catch (error) {
        console.error('Erreur vérification statut push:', error);
        statusText.textContent = 'Erreur de vérification';
        statusText.className = 'small push-status-error';
        return false;
    }
}

// Initialisation du composant flottant
document.addEventListener('DOMContentLoaded', () => {
    // Vérifier si déjà fermé récemment (dans les 24h)
    const dismissedTime = localStorage.getItem(PUSH_FLOAT_CONFIG.dismissKey);
    if (dismissedTime) {
        const hoursSinceDismissed = (Date.now() - parseInt(dismissedTime)) / (1000 * 60 * 60);
        if (hoursSinceDismissed < 24 && !PUSH_FLOAT_CONFIG.showAlways) {
            pushFloatDismissed = true;
            return;
        }
    }
    
    // Vérifier périodiquement le statut
    pushFloatCheckInterval = setInterval(async () => {
        const shouldShow = await checkPushFloatStatus();
        
        if (shouldShow && !pushFloatVisible && !pushFloatDismissed) {
            showPushFloat();
        }
    }, PUSH_FLOAT_CONFIG.checkInterval);
    
    // Première vérification immédiate
    setTimeout(async () => {
        const shouldShow = await checkPushFloatStatus();
        if (shouldShow) {
            showPushFloat();
        }
    }, 2000); // Attendre 2s que le pushManager soit initialisé
});

// Nettoyage
window.addEventListener('beforeunload', () => {
    if (pushFloatCheckInterval) {
        clearInterval(pushFloatCheckInterval);
    }
});
</script>