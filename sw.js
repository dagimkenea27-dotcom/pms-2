// sw.js
self.addEventListener('install', (e) => {
    e.waitUntil(
        caches.open('ims-store').then((cache) => cache.addAll([
            'assets/css/custom.css',
            'assets/js/theme.js'
        ])),
    );
});

self.addEventListener('fetch', (e) => {
    // Correctly allow PHP files to pass through to the server
    if (e.request.url.includes('.php')) {
        return; // Bypass service worker for PHP files
    }

    e.respondWith(
        caches.match(e.request).then((response) => response || fetch(e.request)),
    );
});
