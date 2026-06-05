const CACHE_NAME = 'tag-cicc-shell-v1';
const SHELL_ASSETS = [
    '/',
    '/manifest.webmanifest',
    '/images/tag-cicc-icon.png',
    '/images/tag-cicc-logo.png'
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => cache.addAll(SHELL_ASSETS))
    );
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) => Promise.all(
            cacheNames
                .filter((cacheName) => cacheName !== CACHE_NAME)
                .map((cacheName) => caches.delete(cacheName))
        ))
    );
    self.clients.claim();
});

self.addEventListener('fetch', (event) => {
    if (event.request.method !== 'GET') {
        return;
    }

    const requestUrl = new URL(event.request.url);
    const isStaticAsset = requestUrl.origin === self.location.origin
        && (
            requestUrl.pathname.startsWith('/build/')
            || requestUrl.pathname.startsWith('/images/')
            || requestUrl.pathname === '/manifest.webmanifest'
            || requestUrl.pathname === '/favicon.ico'
        );

    if (!isStaticAsset) {
        event.respondWith(fetch(event.request).catch(() => caches.match('/')));

        return;
    }

    event.respondWith(
        fetch(event.request)
            .then((response) => {
                const copy = response.clone();

                caches.open(CACHE_NAME).then((cache) => cache.put(event.request, copy));

                return response;
            })
            .catch(() => caches.match(event.request).then((response) => response || caches.match('/')))
    );
});
