/*
 * Mochi Cards – Service Worker (App-Shell)
 *
 * Strategie bewusst konservativ, weil Shop + Admin:
 *  - /build/*, /fonts/*, /icons/*  → Cache-first (Dateinamen sind gehasht/stabil)
 *  - /storage/* (Produktbilder)    → Stale-while-revalidate, begrenzte Anzahl
 *  - HTML-Seiten                   → Network-first, Offline-Seite als Fallback
 *  - Admin, Livewire, Checkout, Warenkorb, Webhooks, POST → nie anfassen (immer Netzwerk)
 */
const VERSION = 'mochi-v1';
const STATIC_CACHE = `${VERSION}-static`;
const MEDIA_CACHE = `${VERSION}-media`;
const PAGE_CACHE = `${VERSION}-pages`;
const OFFLINE_URL = '/offline';
const MEDIA_LIMIT = 120;
const PAGE_LIMIT = 25;

const NEVER_CACHE = [
    /^\/admin/,
    /^\/livewire/,
    /^\/cart/,
    /^\/checkout/,
    /^\/webhooks/,
    /^\/api\//,
    /^\/filament/,
    /^\/up$/,
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches
            .open(STATIC_CACHE)
            .then((cache) => cache.addAll([OFFLINE_URL, '/icons/icon-192.png', '/favicon.svg']))
            .then(() => self.skipWaiting()),
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        (async () => {
            const keys = await caches.keys();
            await Promise.all(keys.filter((k) => !k.startsWith(VERSION)).map((k) => caches.delete(k)));
            if (self.registration.navigationPreload) {
                await self.registration.navigationPreload.enable();
            }
            await self.clients.claim();
        })(),
    );
});

async function trim(cacheName, max) {
    const cache = await caches.open(cacheName);
    const keys = await cache.keys();
    for (let i = 0; i < keys.length - max; i++) {
        await cache.delete(keys[i]);
    }
}

function cacheable(response) {
    if (!response || response.status !== 200 || response.type === 'opaque') return false;
    const cc = response.headers.get('Cache-Control') || '';
    return !/no-store|private/i.test(cc);
}

self.addEventListener('fetch', (event) => {
    const { request } = event;
    if (request.method !== 'GET') return;

    const url = new URL(request.url);
    if (url.origin !== self.location.origin) return;
    if (NEVER_CACHE.some((re) => re.test(url.pathname))) return;

    // Gehashte Build-Assets, Fonts, Icons: Cache-first
    if (/^\/(build|fonts|icons)\//.test(url.pathname)) {
        event.respondWith(
            caches.match(request).then(
                (hit) =>
                    hit ||
                    fetch(request).then((res) => {
                        if (res.status === 200) {
                            const copy = res.clone();
                            caches.open(STATIC_CACHE).then((c) => c.put(request, copy));
                        }
                        return res;
                    }),
            ),
        );
        return;
    }

    // Produkt-/Event-Bilder: sofort aus dem Cache, im Hintergrund aktualisieren
    if (url.pathname.startsWith('/storage/') && request.destination === 'image') {
        event.respondWith(
            caches.open(MEDIA_CACHE).then(async (cache) => {
                const hit = await cache.match(request);
                const network = fetch(request)
                    .then((res) => {
                        if (res.status === 200) {
                            cache.put(request, res.clone());
                            trim(MEDIA_CACHE, MEDIA_LIMIT);
                        }
                        return res;
                    })
                    .catch(() => hit);
                return hit || network;
            }),
        );
        return;
    }

    // Seiten: immer Netzwerk (Preise/Lager aktuell, Sessions privat); offline → Offline-Seite.
    // Nur explizit öffentlich cachebare Antworten werden für den Offline-Fall gemerkt.
    if (request.mode === 'navigate') {
        event.respondWith(
            (async () => {
                try {
                    const preload = await event.preloadResponse;
                    const res = preload || (await fetch(request));
                    if (cacheable(res) && /public/i.test(res.headers.get('Cache-Control') || '')) {
                        const copy = res.clone();
                        caches.open(PAGE_CACHE).then((c) => c.put(request, copy).then(() => trim(PAGE_CACHE, PAGE_LIMIT)));
                    }
                    return res;
                } catch (e) {
                    return (await caches.match(request)) || (await caches.match(OFFLINE_URL));
                }
            })(),
        );
    }
});

self.addEventListener('message', (event) => {
    if (event.data === 'skip-waiting') self.skipWaiting();
});
