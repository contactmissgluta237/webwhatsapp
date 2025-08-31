<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Push Notifications Diagnostic') }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="vapid-public-key" content="{{ config('webpush.vapid.public_key') }}">
    <meta name="user-id" content="{{ auth()->id() }}">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen py-8">
    <div class="max-w-4xl mx-auto px-4">
        <div class="bg-white rounded-lg shadow-lg p-8">
            <div class="text-center mb-8">
                <div class="text-6xl mb-4">🔧</div>
                <h1 class="text-3xl font-bold text-gray-800 mb-2">{{ __('Push Notifications Diagnostic') }}</h1>
                <p class="text-gray-600">{{ __('Complete configuration verification') }}</p>
            </div>

            <!-- Status global -->
            <div class="mb-8 p-4 rounded-lg {{ $diagnostics['overall_status'] === 'success' ? 'bg-green-50 border border-green-200' : ($diagnostics['overall_status'] === 'warning' ? 'bg-yellow-50 border border-yellow-200' : 'bg-red-50 border border-red-200') }}">
                <div class="text-center">
                    <span class="text-2xl">
                        @if($diagnostics['overall_status'] === 'success') ✅
                        @elseif($diagnostics['overall_status'] === 'warning') ⚠️  
                        @else ❌
                        @endif
                    </span>
                    <h2 class="text-xl font-semibold {{ $diagnostics['overall_status'] === 'success' ? 'text-green-800' : ($diagnostics['overall_status'] === 'warning' ? 'text-yellow-800' : 'text-red-800') }}">
                        @if($diagnostics['overall_status'] === 'success') Configuration OK
                        @elseif($diagnostics['overall_status'] === 'warning') Problèmes détectés
                        @else Erreurs critiques
                        @endif
                    </h2>
                </div>
            </div>

            <!-- Tests côté client -->
            <div class="mb-8">
                <h3 class="text-lg font-semibold mb-4">🖥️ Tests côté navigateur</h3>
                <div class="space-y-3">
                    <div id="browser-support" class="flex justify-between items-center p-3 bg-gray-50 rounded">
                        <span>Support navigateur</span>
                        <span id="browser-support-status" class="px-2 py-1 rounded text-sm">⏳ En cours...</span>
                    </div>
                    <div id="notification-permission" class="flex justify-between items-center p-3 bg-gray-50 rounded">
                        <span>Permission notifications</span>
                        <span id="notification-permission-status" class="px-2 py-1 rounded text-sm">⏳ En cours...</span>
                    </div>
                    <div id="service-worker" class="flex justify-between items-center p-3 bg-gray-50 rounded">
                        <span>Service Worker</span>
                        <span id="service-worker-status" class="px-2 py-1 rounded text-sm">⏳ En cours...</span>
                    </div>
                    <div id="vapid-key" class="flex justify-between items-center p-3 bg-gray-50 rounded">
                        <span>Clé VAPID disponible</span>
                        <span id="vapid-key-status" class="px-2 py-1 rounded text-sm">⏳ En cours...</span>
                    </div>
                </div>
            </div>

            <!-- Diagnostic serveur -->
            @foreach($diagnostics as $category => $checks)
                @if($category !== 'overall_status')
                <div class="mb-6">
                    <h3 class="text-lg font-semibold mb-4 capitalize">
                        @if($category === 'config') ⚙️ Configuration
                        @elseif($category === 'environment') 🌍 Environnement  
                        @elseif($category === 'database') 🗄️ Base de données
                        @else 🔒 {{ $category }}
                        @endif
                    </h3>
                    <div class="space-y-2">
                        @foreach($checks as $check => $result)
                        <div class="flex justify-between items-center p-3 bg-gray-50 rounded">
                            <div>
                                <span class="font-medium">{{ ucfirst(str_replace('_', ' ', $check)) }}</span>
                                <div class="text-sm text-gray-600">{{ $result['message'] }}</div>
                            </div>
                            <div class="text-right">
                                <span class="px-2 py-1 rounded text-sm {{ $result['status'] === 'success' ? 'bg-green-100 text-green-800' : ($result['status'] === 'warning' ? 'bg-yellow-100 text-yellow-800' : ($result['status'] === 'error' ? 'bg-red-100 text-red-800' : 'bg-blue-100 text-blue-800')) }}">
                                    @if($result['status'] === 'success') ✅
                                    @elseif($result['status'] === 'warning') ⚠️
                                    @elseif($result['status'] === 'error') ❌  
                                    @else ℹ️
                                    @endif
                                    {{ $result['status'] }}
                                </span>
                                <div class="text-sm text-gray-600 mt-1">{{ $result['value'] }}</div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            @endforeach

            <!-- Actions -->
            <div class="mt-8 space-y-3">
                <div class="border-t pt-6">
                    <h3 class="text-lg font-semibold mb-4">🛠️ Actions de test</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <button onclick="runClientDiagnostics()" 
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                            🔄 Refaire les tests navigateur
                        </button>
                        <button onclick="testDirectPush()" 
                                class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                            🔥 Test push direct (debug)
                        </button>
                        <button onclick="testSimpleNotification()" 
                                class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
                            ⚡ Test notification simple
                        </button>
                        <button onclick="testNotification()" 
                                class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                            🧪 Tester une notification
                        </button>
                        <button onclick="window.location.reload()" 
                                class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                            🔄 Actualiser le diagnostic
                        </button>
                        <button onclick="window.history.back()" 
                                class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                            ← Retour
                        </button>
                    </div>
                </div>
            </div>

            <!-- Logs en temps réel -->
            <div class="mt-8">
                <h3 class="text-lg font-semibold mb-4">📋 Logs de diagnostic</h3>
                <div id="diagnostic-logs" class="bg-black text-green-400 p-4 rounded-lg font-mono text-sm h-40 overflow-y-auto">
                    <div>🚀 Démarrage du diagnostic côté client...</div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function log(message) {
            const logs = document.getElementById('diagnostic-logs');
            const now = new Date().toLocaleTimeString();
            logs.innerHTML += `<div>[${now}] ${message}</div>`;
            logs.scrollTop = logs.scrollHeight;
        }

        function setStatus(elementId, status, message) {
            const element = document.getElementById(elementId);
            const statusMap = {
                'success': { class: 'bg-green-100 text-green-800', icon: '✅' },
                'error': { class: 'bg-red-100 text-red-800', icon: '❌' },
                'warning': { class: 'bg-yellow-100 text-yellow-800', icon: '⚠️' }
            };
            
            const statusInfo = statusMap[status] || { class: 'bg-gray-100 text-gray-800', icon: '❓' };
            element.className = `px-2 py-1 rounded text-sm ${statusInfo.class}`;
            element.textContent = `${statusInfo.icon} ${message}`;
        }

        async function runClientDiagnostics() {
            log('🔍 Démarrage des tests côté navigateur...');
            
            // Test du support navigateur
            const isSupported = 'Notification' in window && 'serviceWorker' in navigator && 'PushManager' in window;
            setStatus('browser-support-status', isSupported ? 'success' : 'error', 
                     isSupported ? 'Supporté' : 'Non supporté');
            log(`📱 Support navigateur: ${isSupported ? 'OK' : 'NOK'}`);
            
            // Test permission notifications
            if ('Notification' in window) {
                const permission = Notification.permission;
                const permissionStatus = permission === 'granted' ? 'success' : 
                                       permission === 'denied' ? 'error' : 'warning';
                setStatus('notification-permission-status', permissionStatus, permission);
                log(`🔔 Permission notifications: ${permission}`);
            } else {
                setStatus('notification-permission-status', 'error', 'Non disponible');
                log('❌ API Notification non disponible');
            }
            
            // Test Service Worker
            if ('serviceWorker' in navigator) {
                try {
                    const registration = await navigator.serviceWorker.register('/sw.js');
                    setStatus('service-worker-status', 'success', 'Enregistré');
                    log('✅ Service Worker enregistré avec succès');
                } catch (error) {
                    setStatus('service-worker-status', 'error', 'Erreur');
                    log(`❌ Erreur Service Worker: ${error.message}`);
                }
            } else {
                setStatus('service-worker-status', 'error', 'Non supporté');
                log('❌ Service Workers non supportés');
            }
            
            // Test clé VAPID
            const vapidKey = document.querySelector('meta[name="vapid-public-key"]')?.getAttribute('content');
            setStatus('vapid-key-status', vapidKey ? 'success' : 'error', 
                     vapidKey ? 'Trouvée' : 'Manquante');
            log(`🔑 Clé VAPID: ${vapidKey ? 'Disponible' : 'Manquante'}`);
            
            log('✨ Diagnostic côté navigateur terminé');
        }

        function testNotification() {
            log('🧪 Redirection vers le test de notification...');
            window.location.href = '/test/notification';
        }

        function testSimpleNotification() {
            log('⚡ Test d\'une notification simple...');
            // Vérifier la permission
            if (Notification.permission === 'granted') {
                new Notification('Ceci est un test de notification simple', {
                    body: 'Si vous voyez ceci, les notifications fonctionnent !',
                    icon: '/path/to/icon.png' // Remplacez par le chemin de votre icône
                });
                log('✅ Notification simple affichée');
            } else {
                log('⚠️ Permission de notification non accordée');
            }
        }

        async function testDirectPush() {
            log('🔥 Test d\'un push direct depuis le Service Worker...');
            
            if ('serviceWorker' in navigator) {
                try {
                    const registration = await navigator.serviceWorker.ready;
                    log('📡 Service Worker prêt, simulation d\'un événement push...');
                    
                    const testPayload = JSON.stringify({
                        title: '🔥 Test Push Direct',
                        body: 'Ceci est un test direct du Service Worker',
                        icon: '/favicon.ico',
                        tag: 'direct-test-' + Date.now()
                    });
                    
                    if (registration.active) {
                        registration.active.postMessage({
                            type: 'TEST_PUSH',
                            payload: testPayload
                        });
                        log('💌 Message envoyé au Service Worker');
                    } else {
                        log('❌ Service Worker non actif');
                    }
                    
                } catch (error) {
                    log(`❌ Erreur test direct: ${error.message}`);
                }
            } else {
                log('❌ Service Workers non supportés');
            }
        }

        async function testBackendDebug() {
            log('🔍 Test backend avec debug avancé...');
            
            try {
                // Vérifier d'abord la subscription
                if ('serviceWorker' in navigator) {
                    const registration = await navigator.serviceWorker.ready;
                    const subscription = await registration.pushManager.getSubscription();
                    
                    if (subscription) {
                        log(`✅ Subscription active: ${subscription.endpoint.substring(0,50)}...`);
                        log(`🔑 Clés disponibles: p256dh=${!!subscription.getKey('p256dh')}, auth=${!!subscription.getKey('auth')}`);
                    } else {
                        log('❌ Aucune subscription active');
                        return;
                    }
                }
                
                // Tester l'endpoint backend
                log('📡 Envoi vers /test/notification...');
                const response = await fetch('/test/notification', {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                    }
                });
                
                log(`📊 Réponse HTTP: ${response.status} ${response.statusText}`);
                
                if (response.ok) {
                    const data = await response.json();
                    log(`✅ Backend success: ${JSON.stringify(data)}`);
                    log('🔍 Vérifiez maintenant les logs Laravel pour voir si WebPush a échoué');
                } else {
                    const errorText = await response.text();
                    log(`❌ Erreur backend: ${errorText}`);
                }
                
            } catch (error) {
                log(`💥 Erreur lors du test: ${error.message}`);
            }
        }

        // Lancer le diagnostic automatiquement
        document.addEventListener('DOMContentLoaded', runClientDiagnostics);
    </script>

    <script src="{{ asset('js/push-notifications.js') }}"></script>
</body>
</html>