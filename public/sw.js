/**
 * Service Worker for SalesMgt
 * 
 * Provides offline caching for critical pages (POS, Expenses)
 */

const CACHE_NAME = 'salesmgt-v1';
const OFFLINE_URL = '/offline';

// Pages to cache for offline use
const CRITICAL_PAGES = [
    '/pos',
    '/expenses',
];

// Assets to pre-cache
const PRECACHE_ASSETS = [
    '/offline',
];

// Install event - pre-cache critical assets
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.addAll(PRECACHE_ASSETS);
        })
    );
    self.skipWaiting();
});

// Activate event - clean up old caches
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames
                    .filter((name) => name !== CACHE_NAME)
                    .map((name) => caches.delete(name))
            );
        })
    );
    self.clients.claim();
});

// Fetch event - serve from cache or network
self.addEventListener('fetch', (event) => {
    const url = new URL(event.request.url);
    
    // Only handle same-origin requests
    if (url.origin !== location.origin) {
        return;
    }

    // Don't cache API requests, Livewire updates, or POST requests
    if (
        event.request.method !== 'GET' ||
        url.pathname.startsWith('/api/') ||
        url.pathname.startsWith('/livewire/')
    ) {
        return;
    }

    // For navigation requests (HTML pages)
    if (event.request.mode === 'navigate') {
        event.respondWith(
            fetch(event.request)
                .then((response) => {
                    // Cache critical pages
                    if (CRITICAL_PAGES.some(page => url.pathname.startsWith(page))) {
                        const responseClone = response.clone();
                        caches.open(CACHE_NAME).then((cache) => {
                            cache.put(event.request, responseClone);
                        });
                    }
                    return response;
                })
                .catch(() => {
                    // Try to serve from cache
                    return caches.match(event.request).then((cachedResponse) => {
                        if (cachedResponse) {
                            return cachedResponse;
                        }
                        // Return offline page as fallback
                        return caches.match(OFFLINE_URL);
                    });
                })
        );
        return;
    }

    // For static assets (JS, CSS, images)
    if (
        url.pathname.startsWith('/build/') ||
        url.pathname.match(/\.(js|css|png|jpg|jpeg|gif|svg|ico|woff|woff2)$/)
    ) {
        event.respondWith(
            caches.match(event.request).then((cachedResponse) => {
                if (cachedResponse) {
                    // Return cached version and update in background
                    fetch(event.request).then((response) => {
                        caches.open(CACHE_NAME).then((cache) => {
                            cache.put(event.request, response);
                        });
                    }).catch(() => {});
                    return cachedResponse;
                }

                return fetch(event.request).then((response) => {
                    const responseClone = response.clone();
                    caches.open(CACHE_NAME).then((cache) => {
                        cache.put(event.request, responseClone);
                    });
                    return response;
                });
            })
        );
        return;
    }
});

// Listen for messages from the main thread
self.addEventListener('message', (event) => {
    if (event.data === 'skipWaiting') {
        self.skipWaiting();
    }
});
