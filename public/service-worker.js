const CACHE_NAME = 'my-app-cache-v1';
const urlsToCache = [
    '/',
    '/css/app.css', // Adjust these paths to your actual CSS and JS files
    '/js/app.js',   // These are common paths for Laravel Mix/Vite compiled assets
    // Add other static assets you want to cache, e.g., images, fonts
];

self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => {
                console.log('Opened cache');
                return cache.addAll(urlsToCache);
            })
    );
});

self.addEventListener('fetch', event => {
    event.respondWith(
        caches.match(event.request)
            .then(response => {
                // Cache hit - return response
                if (response) {
                    return response;
                }
                return fetch(event.request);
            })
    );
});