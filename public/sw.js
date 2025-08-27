// Service Worker - Push notifications

const CACHE_NAME = 'laravel-app-v1';

// Initialization - Service Worker loaded

// Listen to messages from main page
self.addEventListener('message', (event) => {
    // Message received in Service Worker
    
    if (event.data && event.data.type === 'TEST_PUSH') {
        // Push event simulation requested
        
        try {
            const payload = JSON.parse(event.data.payload);
            // Test payload received
            
            // Simulate direct notification display
            self.registration.showNotification(payload.title, {
                body: payload.body,
                icon: payload.icon,
                tag: payload.tag,
                requireInteraction: true
            }).then(() => {
                // Test notification displayed via message
            }).catch((error) => {
                console.error('Notification test error:', error);
            });
            
        } catch (error) {
            console.error('Test payload parsing error:', error);
        }
    }
});

self.addEventListener('install', (event) => {
    // Service Worker installation
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    // Service Worker activation
    event.waitUntil(
        self.clients.claim().then(() => {
            // Service Worker claimed control of all clients
        })
    );
});

// Push event handler
self.addEventListener('push', (event) => {
    // Push event received
    
    let data = {};
    let rawPayload = '';
    
    if (event.data) {
        try {
            rawPayload = event.data.text();
            // Raw payload received
            
            data = event.data.json();
            // JSON parsed successfully
        } catch (parseError) {
            console.error('JSON parsing error:', parseError);
            // Attempting with raw text
            
            data = {
                title: '🔧 Debug Notification',
                body: rawPayload || 'Notification sans données JSON',
                icon: '/favicon.ico'
            };
        }
    } else {
        // No data in push event
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

    // Final notification options prepared

    const showNotificationPromise = self.registration.showNotification(
        notificationOptions.title, 
        notificationOptions
    ).then(() => {
        // Notification displayed successfully
        
        // Get active notifications
        return self.registration.getNotifications();
    }).then((notifications) => {
        // Active notifications count logged
    }).catch((error) => {
        console.error('Critical error displaying notification:', error);
        console.error('Full stack trace:', error.stack);
        
        // Fallback notification
        return self.registration.showNotification('Debug Error', {
            body: `Erreur: ${error.message}`,
            icon: '/favicon.ico',
            tag: 'error-debug'
        });
    });

    event.waitUntil(showNotificationPromise);
});

// Notification click handler
self.addEventListener('notificationclick', (event) => {
    // Notification clicked
    
    event.notification.close();
    
    if (event.action === 'view') {
        // View action clicked
    }
});

// Service Worker heartbeat disabled to reduce console pollution
// Configuration completed