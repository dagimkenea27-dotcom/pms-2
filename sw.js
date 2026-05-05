// sw.js v4 - Enhanced PWA & Offline Support
const CACHE_NAME = 'ims-store-v4';
const OFFLINE_URL = 'offline.php';

const ASSETS_TO_CACHE = [
    'assets/css/custom.css',
    'assets/js/theme.js',
    'assets/js/sidebar.js',
    'assets/js/alerts.js',
    'assets/img/logo.jpg',
    OFFLINE_URL
];

// Install Event
self.addEventListener('install', (e) => {
    self.skipWaiting(); // Force active immediately
    e.waitUntil(
        caches.open(CACHE_NAME).then((cache) => cache.addAll(ASSETS_TO_CACHE))
    );
});

// Activate Event - Clean up old caches
self.addEventListener('activate', (e) => {
    e.waitUntil(
        caches.keys().then((keys) => {
            return Promise.all(
                keys.map((key) => {
                    if (key !== CACHE_NAME) {
                        return caches.delete(key);
                    }
                })
            );
        })
    );
    self.clients.claim();
});

// Fetch Event
self.addEventListener('fetch', (e) => {
    if (e.request.method !== 'GET') return;

    const url = new URL(e.request.url);

    // 1. HTML Navigation requests (Network-first with offline fallback)
    // Only apply offline fallback to actual page navigations (top-level)
    if (e.request.mode === 'navigate') {
        e.respondWith(
            fetch(e.request)
                .catch(() => {
                    // Return the offline page if network fails
                    return caches.match(OFFLINE_URL);
                })
        );
        return;
    }

    // 2. Static Assets (Stale-While-Revalidate)
    e.respondWith(
        caches.match(e.request).then((cachedResponse) => {
            const fetchPromise = fetch(e.request).then((networkResponse) => {
                // IMPORTANT: Clone the response BEFORE returning it
                const responseToCache = networkResponse.clone();
                caches.open(CACHE_NAME).then((cache) => {
                    cache.put(e.request, responseToCache);
                });
                return networkResponse;
            }).catch(() => {
                // Ignore network errors on asset fetches if we have cache
                return new Response('Not found', { status: 404 });
            });
            return cachedResponse || fetchPromise;
        })
    );
});
