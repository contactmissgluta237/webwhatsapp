// Service Worker Debug - Version avancée pour diagnostic

const CACHE_NAME = 'laravel-app-v1';

// Log d'initialisation détaillé
console.log('🚀 Service Worker - Chargement initial');
console.log('📍 URL Service Worker:', self.location.href);
console.log('🌐 Scope:', self.registration?.scope);

// Écouter les messages depuis la page principale
self.addEventListener('message', (event) => {
    console.log('💌 Message reçu dans Service Worker:', event.data);
    
    if (event.data && event.data.type === 'TEST_PUSH') {
        console.log('🔥 Simulation d\'événement push demandée');
        
        try {
            const payload = JSON.parse(event.data.payload);
            console.log('📦 Payload de test:', payload);
            
            // Simuler l'affichage direct d'une notification
            self.registration.showNotification(payload.title, {
                body: payload.body,
                icon: payload.icon,
                tag: payload.tag,
                requireInteraction: true
            }).then(() => {
                console.log('✅ Notification de test affichée via message');
            }).catch((error) => {
                console.error('❌ Erreur notification test:', error);
            });
            
        } catch (error) {
            console.error('❌ Erreur parsing payload test:', error);
        }
    }
});

self.addEventListener('install', (event) => {
    console.log('🔧 Service Worker - Installation en cours');
    console.log('📦 Event install:', event);
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    console.log('✅ Service Worker - Activation en cours');
    console.log('📦 Event activate:', event);
    event.waitUntil(
        self.clients.claim().then(() => {
            console.log('👑 Service Worker - Contrôle de tous les clients pris');
        })
    );
});

// Event listener pour debug complet
self.addEventListener('push', (event) => {
    console.log('🚨 PUSH EVENT REÇU !');
    console.log('📦 Event complet:', event);
    console.log('📊 Event.data existe:', !!event.data);
    console.log('🔍 Type de event.data:', typeof event.data);
    
    let data = {};
    let rawPayload = '';
    
    if (event.data) {
        try {
            rawPayload = event.data.text();
            console.log('📝 Payload brut reçu:', rawPayload);
            console.log('📏 Taille payload:', rawPayload.length, 'caractères');
            
            data = event.data.json();
            console.log('✅ JSON parsé avec succès:', data);
            console.log('🏷️ Titre détecté:', data.title);
            console.log('📄 Body détecté:', data.body);
        } catch (parseError) {
            console.error('❌ Erreur parsing JSON:', parseError);
            console.log('🔄 Tentative avec texte brut:', rawPayload);
            
            data = {
                title: '🔧 Debug Notification',
                body: rawPayload || 'Notification sans données JSON',
                icon: '/favicon.ico'
            };
        }
    } else {
        console.warn('⚠️ Aucune donnée dans l\'event push');
        data = {
            title: '🔔 Test Service Worker',
            body: 'Event push reçu mais sans données',
            icon: '/favicon.ico'
        };
    }

    const notificationOptions = {
        title: data.title || 'Notification Debug',
        body: data.body || 'Corps par défaut',
        icon: data.icon || '/favicon.ico',
        badge: data.badge || '/favicon.ico',
        tag: 'debug-' + Date.now(),
        data: { 
            ...data.data, 
            debug: true, 
            timestamp: Date.now(),
            rawPayload: rawPayload 
        },
        requireInteraction: true,
        silent: false,
        vibrate: [200, 100, 200],
        actions: [
            { action: 'view', title: '👀 Voir' },
            { action: 'close', title: '❌ Fermer' }
        ]
    };

    console.log('🎯 Options notification finales:', JSON.stringify(notificationOptions, null, 2));

    const showNotificationPromise = self.registration.showNotification(
        notificationOptions.title, 
        notificationOptions
    ).then(() => {
        console.log('✅ Notification affichée avec SUCCÈS');
        console.log('👀 Vérifiez votre zone de notification système');
        
        // Test supplémentaire - Lister les notifications actives
        return self.registration.getNotifications();
    }).then((notifications) => {
        console.log('📋 Notifications actives:', notifications.length);
        notifications.forEach((notif, index) => {
            console.log(`📌 Notification ${index + 1}:`, notif.title);
        });
    }).catch((error) => {
        console.error('💥 ERREUR CRITIQUE lors de l\'affichage:', error);
        console.error('📊 Stack trace complète:', error.stack);
        
        // Notification de secours
        return self.registration.showNotification('🆘 Erreur Debug', {
            body: `Erreur: ${error.message}`,
            icon: '/favicon.ico',
            tag: 'error-debug'
        });
    });

    event.waitUntil(showNotificationPromise);
});

// Debug des clics
self.addEventListener('notificationclick', (event) => {
    console.log('👆 Notification cliquée - Debug');
    console.log('📦 Event:', event);
    console.log('🏷️ Notification:', event.notification);
    console.log('🎬 Action:', event.action);
    
    event.notification.close();
    
    if (event.action === 'view') {
        console.log('🔗 Action "voir" cliquée');
    }
});

// Test périodique pour vérifier que le SW est vivant
setInterval(() => {
    console.log('💓 Service Worker - Heartbeat:', new Date().toLocaleTimeString());
}, 30000);

console.log('🏁 Service Worker - Configuration debug terminée');
