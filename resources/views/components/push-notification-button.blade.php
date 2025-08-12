<!-- resources/views/components/push-notification-button.blade.php -->

<div id="push-notification-container" class="d-flex align-items-center">
    <button id="enable-notifications-btn" class="btn btn-whatsapp mr-2 d-none" onclick="enableNotifications()"
        style="display: none !important;">
        <i class="ft-bell mr-1"></i>
        <span>Activer les notifications</span>
    </button>

    <button id="disable-notifications-btn" class="btn btn-danger mr-2 d-none" onclick="disableNotifications()"
        style="display: none !important;">
        <i class="ft-bell-off mr-1"></i>
        <span>Désactiver les notifications</span>
    </button>

    <div id="notification-status" class="text-muted small">
        <span id="status-text">Vérification du statut...</span>
    </div>
</div>

<script>
    function checkPushNotificationSupport() {
        if (!('Notification' in window)) {
            return false;
        }

        if (!('serviceWorker' in navigator)) {
            return false;
        }

        if (!('PushManager' in window)) {
            return false;
        }

        return true;
    }

    async function enableNotifications() {
        try {
            const enableBtn = document.getElementById('enable-notifications-btn');
            const statusText = document.getElementById('status-text');

            enableBtn.disabled = true;
            statusText.textContent = 'Activation en cours...';

            if (!checkPushNotificationSupport()) {
                throw new Error('Les notifications push ne sont pas supportées par ce navigateur');
            }

            if (!window.pushManager) {
                throw new Error('Gestionnaire de notifications non initialisé');
            }

            await window.pushManager.subscribe();

            statusText.textContent = 'Notifications activées ✅';
            updateButtonsVisibility(true);

        } catch (error) {
            console.error('Erreur lors de l\'activation:', error);
            document.getElementById('status-text').textContent = 'Erreur: ' + error.message;
            document.getElementById('enable-notifications-btn').disabled = false;
        }
    }

    async function disableNotifications() {
        try {
            const disableBtn = document.getElementById('disable-notifications-btn');
            const statusText = document.getElementById('status-text');

            disableBtn.disabled = true;
            statusText.textContent = 'Désactivation en cours...';

            if (!window.pushManager) {
                throw new Error('Gestionnaire de notifications non initialisé');
            }

            await window.pushManager.unsubscribe();

            statusText.textContent = 'Notifications désactivées';
            updateButtonsVisibility(false);

        } catch (error) {
            console.error('Erreur lors de la désactivation:', error);
            document.getElementById('status-text').textContent = 'Erreur: ' + error.message;
            document.getElementById('disable-notifications-btn').disabled = false;
        }
    }

    function updateButtonsVisibility(isSubscribed) {
        const enableBtn = document.getElementById('enable-notifications-btn');
        const disableBtn = document.getElementById('disable-notifications-btn');

        if (isSubscribed) {
            enableBtn.classList.add('d-none');
            enableBtn.style.display = 'none';
            disableBtn.classList.remove('d-none');
            disableBtn.style.display = 'inline-block';
        } else {
            enableBtn.classList.remove('d-none');
            enableBtn.style.display = 'inline-block';
            disableBtn.classList.add('d-none');
            disableBtn.style.display = 'none';
        }

        enableBtn.disabled = false;
        disableBtn.disabled = false;
    }

    // Vérifier le statut au chargement
    document.addEventListener('DOMContentLoaded', async () => {
        const statusText = document.getElementById('status-text');

        // D'abord vérifier le support natif
        if (!checkPushNotificationSupport()) {
            statusText.textContent = 'Non supporté par ce navigateur';
            return;
        }

        // Attendre que le gestionnaire soit initialisé
        let attempts = 0;
        const maxAttempts = 10;

        const checkManager = async () => {
            if (window.pushManager) {
                try {
                    const isSubscribed = await window.pushManager.getSubscriptionStatus();

                    if (isSubscribed) {
                        statusText.textContent = 'Notifications activées ✅';
                        updateButtonsVisibility(true);
                    } else {
                        statusText.textContent = 'Notifications non activées';
                        updateButtonsVisibility(false);
                    }
                } catch (error) {
                    console.error('Erreur lors de la vérification:', error);
                    statusText.textContent = 'Erreur de vérification';
                    updateButtonsVisibility(false);
                }
            } else {
                attempts++;
                if (attempts < maxAttempts) {
                    setTimeout(checkManager, 500);
                } else {
                    statusText.textContent = 'Gestionnaire non disponible';
                    updateButtonsVisibility(false);
                }
            }
        };

        checkManager();
    });
</script>
