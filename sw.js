// sw.js v3 - Production Reliability Update
const CACHE_NAME = 'ims-store-v3';
const ASSETS_TO_CACHE = [
    'assets/css/custom.css',
    'assets/js/theme.js',
    'assets/js/sidebar.js',
    'assets/js/alerts.js'
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
    // 1. Skip non-GET requests
    if (e.request.method !== 'GET') return;

    const url = new URL(e.request.url);

    // 2. EXCLUDE navigation and PHP files from SW caching
    // This solves the "white screen" or "not loading" issue on mobile 
    // when the worker state becomes stale or network is spotty.
    if (e.request.mode === 'navigate' || url.pathname.endsWith('.php') || url.pathname === '/') {
        return;
    }

    // 3. For assets: Cache-First strategy
    e.respondWith(
        caches.match(e.request).then((response) => {
            return response || fetch(e.request).catch(() => {
                // Fallback for failed asset fetch
                return new Response('Not found', { status: 404 });
            });
        })
    );
});
