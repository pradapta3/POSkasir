/**
 * POS Kasir is a server-rendered Livewire app — every screen and action
 * (adding to cart, checkout, reports) is a round trip to the server, so
 * this service worker deliberately does NOT try to cache pages for true
 * offline use; a stale cached POS screen that silently can't actually
 * process a sale would be worse than no offline support at all. What it
 * does do: make the app installable (paired with manifest.json), and
 * swap the browser's own "no internet" error page for a friendlier one
 * when a navigation fails while offline.
 */
const CACHE_NAME = 'pos-kasir-shell-v1';
const OFFLINE_URL = '/offline';
const PRECACHE_URLS = [
    OFFLINE_URL,
    '/icons/icon-192.png',
    '/icons/icon-512.png',
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => cache.addAll(PRECACHE_URLS))
    );
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) =>
            Promise.all(keys.filter((key) => key !== CACHE_NAME).map((key) => caches.delete(key)))
        )
    );
    self.clients.claim();
});

self.addEventListener('fetch', (event) => {
    if (event.request.mode !== 'navigate') {
        return;
    }

    event.respondWith(
        fetch(event.request).catch(() => caches.match(OFFLINE_URL))
    );
});
