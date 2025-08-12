@props(['user'])

<div class="card">
    <div class="card-header">
        <h4 class="card-title">
            <i class="ft-bell mr-2"></i>
            Notifications Push
        </h4>
    </div>
    <div class="card-body">

        <!-- Statut actuel -->
        <div class="alert" id="push-profile-status" role="alert" style="display: none;">
            <div class="d-flex align-items-center">
                <i id="push-profile-icon" class="mr-2"></i>
                <div>
                    <strong id="push-profile-title"></strong>
                    <div id="push-profile-message" class="small"></div>
                </div>
            </div>
        </div>

        <!-- Informations sur le navigateur -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="border rounded p-3 h-100">
                    <h6 class="font-weight-bold mb-2">
                        <i class="ft-info text-info mr-1"></i>
                        Compatibilité
                    </h6>
                    <div id="browser-support-info">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="small">Navigateur:</span>
                            <span id="browser-name" class="badge badge-secondary small">Détection...</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="small">Support Push:</span>
                            <span id="push-support" class="badge badge-secondary small">Vérification...</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="small">Connexion:</span>
                            <span id="connection-type" class="badge badge-secondary small">HTTPS requis</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="border rounded p-3 h-100">
                    <h6 class="font-weight-bold mb-2">
                        <i class="ft-settings text-primary mr-1"></i>
                        Paramètres
                    </h6>
                    <div class="form-group mb-2">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="auto-show-float" checked>
                            <label class="custom-control-label small" for="auto-show-float">
                                Afficher les invitations automatiques
                            </label>
                        </div>
                    </div>
                    <div class="form-group mb-2">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="sound-notifications">
                            <label class="custom-control-label small" for="sound-notifications">
                                Sons de notification (navigateur)
                            </label>
                        </div>
                    </div>
                    <div class="form-group mb-0">
                        <label class="small text-muted">Position de l'invitation:</label>
                        <select class="form-control form-control-sm" id="float-position">
                            <option value="bottom-right">Bas droite</option>
                            <option value="bottom-left">Bas gauche</option>
                            <option value="top-right">Haut droite</option>
                            <option value="top-left">Haut gauche</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Actions principales -->
        <div class="row">
            <div class="col-md-8">
                <div id="push-profile-actions">
                    <button id="profile-enable-push" class="btn btn-whatsapp mr-2" onclick="profileEnablePush()"
                        style="display: none;">
                        <i class="ft-bell mr-1"></i>
                        Activer les notifications
                    </button>

                    <button id="profile-disable-push" class="btn btn-outline-danger mr-2" onclick="profileDisablePush()"
                        style="display: none;">
                        <i class="ft-bell-off mr-1"></i>
                        Désactiver les notifications
                    </button>

                    <button id="profile-test-push" class="btn btn-outline-secondary" onclick="profileTestPush()"
                        style="display: none;">
                        <i class="ft-send mr-1"></i>
                        Tester
                    </button>
                </div>
            </div>
            <div class="col-md-4 text-right">
                <button class="btn btn-link btn-sm" onclick="showPushDiagnostic()" title="Diagnostic avancé">
                    <i class="ft-tool mr-1"></i>
                    Diagnostic
                </button>
            </div>
        </div>

        <!-- Aide et informations -->
        <div class="mt-4">
            <div class="border-top pt-3">
                <h6 class="font-weight-bold mb-2">
                    <i class="ft-help-circle text-info mr-1"></i>
                    Aide et informations
                </h6>
                <div class="row">
                    <div class="col-md-6">
                        <div class="small text-muted mb-2">
                            <strong>Problèmes courants:</strong>
                        </div>
                        <ul class="small text-muted pl-3">
                            <li>Chrome mobile nécessite HTTPS</li>
                            <li>iOS ne supporte pas les notifications web</li>
                            <li>Vérifiez les paramètres du navigateur</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <div class="small text-muted mb-2">
                            <strong>Bénéfices:</strong>
                        </div>
                        <ul class="small text-muted pl-3">
                            <li>Notifications instantanées</li>
                            <li>Même navigateur fermé</li>
                            <li>Gestion centralisée</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Configuration du profil push
    const PUSH_PROFILE_CONFIG = {
        userId: {{ auth()->id() }},
        settingsKey: 'push_profile_settings_' + {{ auth()->id() }},
        statusCheckInterval: 5000
    };

    let pushProfileInterval = null;

    async function profileEnablePush() {
        const button = document.getElementById('profile-enable-push');
        const originalHtml = button.innerHTML;

        try {
            button.disabled = true;
            button.innerHTML = '<i class="ft-clock mr-1"></i> Activation en cours...';

            if (!window.pushManager) {
                throw new Error('Service de notifications non disponible');
            }

            await window.pushManager.subscribe();

            updatePushProfileStatus();
            showPushProfileAlert('success', 'Succès !', 'Les notifications push ont été activées avec succès.');

        } catch (error) {
            console.error('Erreur activation push profile:', error);
            showPushProfileAlert('danger', 'Erreur', error.message);

            button.disabled = false;
            button.innerHTML = originalHtml;
        }
    }

    async function profileDisablePush() {
        const button = document.getElementById('profile-disable-push');
        const originalHtml = button.innerHTML;

        try {
            button.disabled = true;
            button.innerHTML = '<i class="ft-clock mr-1"></i> Désactivation en cours...';

            if (!window.pushManager) {
                throw new Error('Service de notifications non disponible');
            }

            await window.pushManager.unsubscribe();

            updatePushProfileStatus();
            showPushProfileAlert('warning', 'Désactivé', 'Les notifications push ont été désactivées.');

        } catch (error) {
            console.error('Erreur désactivation push profile:', error);
            showPushProfileAlert('danger', 'Erreur', error.message);

            button.disabled = false;
            button.innerHTML = originalHtml;
        }
    }

    async function profileTestPush() {
        const button = document.getElementById('profile-test-push');
        const originalHtml = button.innerHTML;

        try {
            button.disabled = true;
            button.innerHTML = '<i class="ft-clock mr-1"></i> Envoi...';

            if (!window.pushManager) {
                throw new Error('Service de notifications non disponible');
            }

            await window.pushManager.sendTestNotification();

            showPushProfileAlert('info', 'Test envoyé',
                'Une notification de test a été envoyée. Vérifiez vos notifications.');

        } catch (error) {
            console.error('Erreur test push profile:', error);
            showPushProfileAlert('danger', 'Erreur de test', error.message);
        } finally {
            setTimeout(() => {
                button.disabled = false;
                button.innerHTML = originalHtml;
            }, 2000);
        }
    }

    function showPushProfileAlert(type, title, message) {
        const alertElement = document.getElementById('push-profile-status');
        const iconElement = document.getElementById('push-profile-icon');
        const titleElement = document.getElementById('push-profile-title');
        const messageElement = document.getElementById('push-profile-message');

        // Classes pour les types d'alerte
        const alertClasses = {
            success: {
                alert: 'alert-success',
                icon: 'ft-check-circle text-success'
            },
            danger: {
                alert: 'alert-danger',
                icon: 'ft-x-circle text-danger'
            },
            warning: {
                alert: 'alert-warning',
                icon: 'ft-alert-triangle text-warning'
            },
            info: {
                alert: 'alert-info',
                icon: 'ft-info text-info'
            }
        };

        const config = alertClasses[type] || alertClasses.info;

        // Supprimer toutes les classes d'alerte existantes
        alertElement.className = 'alert ' + config.alert;
        iconElement.className = config.icon + ' mr-2';
        titleElement.textContent = title;
        messageElement.textContent = message;

        alertElement.style.display = 'block';

        // Auto-masquer après 5 secondes pour les succès et infos
        if (type === 'success' || type === 'info') {
            setTimeout(() => {
                alertElement.style.display = 'none';
            }, 5000);
        }
    }

    async function updatePushProfileStatus() {
        const enableBtn = document.getElementById('profile-enable-push');
        const disableBtn = document.getElementById('profile-disable-push');
        const testBtn = document.getElementById('profile-test-push');

        try {
            if (!window.pushManager) {
                // Service pas encore disponible
                enableBtn.style.display = 'none';
                disableBtn.style.display = 'none';
                testBtn.style.display = 'none';
                showPushProfileAlert('warning', 'Initialisation',
                    'Service de notifications en cours d\'initialisation...');
                return;
            }

            const isSupported = window.pushManager.isSupported();
            if (!isSupported) {
                const supportInfo = window.pushManager.getSupportInfo();
                enableBtn.style.display = 'none';
                disableBtn.style.display = 'none';
                testBtn.style.display = 'none';
                showPushProfileAlert('danger', 'Non supporté', supportInfo.reason + '. ' + (supportInfo
                    .suggestion || ''));
                updateBrowserSupportInfo(supportInfo);
                return;
            }

            const isSubscribed = await window.pushManager.getSubscriptionStatus();

            if (isSubscribed) {
                enableBtn.style.display = 'none';
                disableBtn.style.display = 'inline-block';
                testBtn.style.display = 'inline-block';
                disableBtn.disabled = false;
                testBtn.disabled = false;
                showPushProfileAlert('success', 'Activées',
                    'Les notifications push sont actives pour votre compte.');
            } else {
                enableBtn.style.display = 'inline-block';
                disableBtn.style.display = 'none';
                testBtn.style.display = 'none';
                enableBtn.disabled = false;
                showPushProfileAlert('info', 'Non activées',
                    'Activez les notifications pour recevoir les alertes importantes.');
            }

            updateBrowserSupportInfo(window.pushManager.getSupportInfo());

        } catch (error) {
            console.error('Erreur mise à jour statut profile:', error);
            showPushProfileAlert('danger', 'Erreur', 'Impossible de vérifier le statut des notifications.');
        }
    }

    function updateBrowserSupportInfo(supportInfo) {
        const browserName = document.getElementById('browser-name');
        const pushSupport = document.getElementById('push-support');
        const connectionType = document.getElementById('connection-type');

        // Détection du navigateur
        const userAgent = navigator.userAgent;
        let browser = 'Inconnu';
        let browserClass = 'badge-secondary';

        if (userAgent.includes('Chrome')) {
            browser = supportInfo.isMobile ? 'Chrome Mobile' : 'Chrome';
            browserClass = 'badge-success';
        } else if (userAgent.includes('Firefox')) {
            browser = 'Firefox';
            browserClass = 'badge-info';
        } else if (userAgent.includes('Safari')) {
            browser = 'Safari';
            browserClass = supportInfo.isIOS ? 'badge-warning' : 'badge-info';
        } else if (userAgent.includes('Edge')) {
            browser = 'Edge';
            browserClass = 'badge-primary';
        }

        browserName.textContent = browser;
        browserName.className = `badge ${browserClass} small`;

        // Support push
        if (supportInfo.isSupported) {
            pushSupport.textContent = 'Supporté';
            pushSupport.className = 'badge badge-success small';
        } else {
            pushSupport.textContent = 'Non supporté';
            pushSupport.className = 'badge badge-danger small';
        }

        // Type de connexion
        const isHttps = location.protocol === 'https:';
        connectionType.textContent = isHttps ? 'HTTPS ✓' : 'HTTP (non sécurisé)';
        connectionType.className = `badge ${isHttps ? 'badge-success' : 'badge-danger'} small`;
    }

    function showPushDiagnostic() {
        window.open('/push/diagnostic', '_blank');
    }

    function loadPushProfileSettings() {
        const saved = localStorage.getItem(PUSH_PROFILE_CONFIG.settingsKey);
        if (saved) {
            try {
                const settings = JSON.parse(saved);

                // Appliquer les paramètres sauvegardés
                if (settings.autoShowFloat !== undefined) {
                    document.getElementById('auto-show-float').checked = settings.autoShowFloat;
                }
                if (settings.soundNotifications !== undefined) {
                    document.getElementById('sound-notifications').checked = settings.soundNotifications;
                }
                if (settings.floatPosition) {
                    document.getElementById('float-position').value = settings.floatPosition;
                }
            } catch (error) {
                console.error('Erreur chargement paramètres push profile:', error);
            }
        }
    }

    function savePushProfileSettings() {
        const settings = {
            autoShowFloat: document.getElementById('auto-show-float').checked,
            soundNotifications: document.getElementById('sound-notifications').checked,
            floatPosition: document.getElementById('float-position').value,
            lastUpdated: Date.now()
        };

        localStorage.setItem(PUSH_PROFILE_CONFIG.settingsKey, JSON.stringify(settings));
    }

    // Initialisation du profil push
    document.addEventListener('DOMContentLoaded', () => {
        loadPushProfileSettings();

        // Écouter les changements de paramètres
        ['auto-show-float', 'sound-notifications', 'float-position'].forEach(id => {
            const element = document.getElementById(id);
            if (element) {
                element.addEventListener('change', savePushProfileSettings);
            }
        });

        // Vérification périodique du statut
        pushProfileInterval = setInterval(updatePushProfileStatus, PUSH_PROFILE_CONFIG.statusCheckInterval);

        // Première vérification
        setTimeout(updatePushProfileStatus, 1000);
    });

    // Nettoyage
    window.addEventListener('beforeunload', () => {
        if (pushProfileInterval) {
            clearInterval(pushProfileInterval);
        }
    });
</script>
