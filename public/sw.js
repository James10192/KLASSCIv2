/* KLASSCI — Service Worker PWA
 * Strategie Workbox (CDN) : navigations network-only + fallback hors ligne,
 * SWR assets, cache images/fonts et notifications web push.
 */

const VERSION = "klassci-v5";

// Caches nommes versionnes pour invalidation propre a l'activation.
const CACHE_NAMES = {
    precache: "klassci-precache-" + VERSION,
    assets: "klassci-assets-" + VERSION,
    cdn: "klassci-cdn-" + VERSION,
    images: "klassci-images-" + VERSION,
    fonts: "klassci-fonts-" + VERSION,
};

// App-shell minimal a precacher (page hors ligne, icone, shell mobile).
const PRECACHE_URLS = [
    "/offline.html",
    "/icons/icon-192.png",
    "/css/mobile-shell.css",
    "/js/mobile-shell.js",
];

// CDN de librairies (Bootstrap, Alpine, jQuery, Chart.js...) : StaleWhileRevalidate
// dans un cache dedie avec expiration, pour que le shell s'ouvre sans reseau.
const CDN_ORIGINS = ["https://cdn.jsdelivr.net", "https://code.jquery.com"];

// Anciens caches a supprimer (dont le SW legacy attendance).
const LEGACY_CACHES = ["esbtp-attendance-v1"];

importScripts(
    "https://storage.googleapis.com/workbox-cdn/releases/7.1.0/workbox-sw.js"
);

if (self.workbox) {
    const {
        core,
        precaching,
        routing,
        strategies,
        expiration,
        cacheableResponse,
    } = self.workbox;

    core.setCacheNameDetails({ prefix: "klassci", suffix: VERSION });

    // ---------------------------------------------------------------------
    // Precache app-shell
    // ---------------------------------------------------------------------
    precaching.precacheAndRoute(
        PRECACHE_URLS.map((url) => ({ url: url, revision: VERSION }))
    );

    // Les documents authentifies sont toujours servis par le reseau. Un
    // redirect de session cache sous /mes-notes ou une page d'administration
    // obsolete pourrait sinon restaurer le dashboard pour une autre URL.
    routing.registerRoute(
        ({ request }) => request.mode === "navigate",
        new strategies.NetworkOnly()
    );

    // ---------------------------------------------------------------------
    // Librairies CDN (jsdelivr, jquery) : StaleWhileRevalidate + expiration.
    // Declaree AVANT la route generique CSS/JS pour capter ces origines
    // quelle que soit la destination (script, style, police embarquee...).
    // ---------------------------------------------------------------------
    routing.registerRoute(
        ({ url }) => CDN_ORIGINS.includes(url.origin),
        new strategies.StaleWhileRevalidate({
            cacheName: CACHE_NAMES.cdn,
            plugins: [
                new cacheableResponse.CacheableResponsePlugin({
                    statuses: [0, 200],
                }),
                new expiration.ExpirationPlugin({
                    maxEntries: 60,
                    maxAgeSeconds: 30 * 24 * 60 * 60, // 30 jours
                }),
            ],
        })
    );

    // ---------------------------------------------------------------------
    // CSS / JS : StaleWhileRevalidate
    // ---------------------------------------------------------------------
    routing.registerRoute(
        ({ request }) =>
            request.destination === "style" ||
            request.destination === "script" ||
            request.destination === "worker",
        new strategies.StaleWhileRevalidate({
            cacheName: CACHE_NAMES.assets,
            plugins: [
                new cacheableResponse.CacheableResponsePlugin({
                    statuses: [0, 200],
                }),
            ],
        })
    );

    // ---------------------------------------------------------------------
    // Images : CacheFirst + expiration
    // ---------------------------------------------------------------------
    routing.registerRoute(
        ({ request }) => request.destination === "image",
        new strategies.CacheFirst({
            cacheName: CACHE_NAMES.images,
            plugins: [
                new cacheableResponse.CacheableResponsePlugin({
                    statuses: [0, 200],
                }),
                new expiration.ExpirationPlugin({
                    maxEntries: 80,
                    maxAgeSeconds: 30 * 24 * 60 * 60, // 30 jours
                }),
            ],
        })
    );

    // ---------------------------------------------------------------------
    // Polices (Google Fonts + Font Awesome CDN)
    // ---------------------------------------------------------------------
    routing.registerRoute(
        ({ url }) => url.origin === "https://fonts.googleapis.com",
        new strategies.StaleWhileRevalidate({
            cacheName: CACHE_NAMES.fonts,
        })
    );

    routing.registerRoute(
        ({ url }) =>
            url.origin === "https://fonts.gstatic.com" ||
            url.hostname.includes("fontawesome") ||
            url.hostname.includes("cdnjs.cloudflare.com"),
        new strategies.CacheFirst({
            cacheName: CACHE_NAMES.fonts,
            plugins: [
                new cacheableResponse.CacheableResponsePlugin({
                    statuses: [0, 200],
                }),
                new expiration.ExpirationPlugin({
                    maxEntries: 30,
                    maxAgeSeconds: 365 * 24 * 60 * 60, // 1 an
                }),
            ],
        })
    );

    // ---------------------------------------------------------------------
    // Fallback offline pour les navigations echouees
    // ---------------------------------------------------------------------
    routing.setCatchHandler(async ({ request }) => {
        if (request.destination === "document") {
            const cached = await caches.match("/offline.html");
            if (cached) return cached;
        }
        return Response.error();
    });
} else {
    // ---------------------------------------------------------------------
    // Fallback robuste si Workbox indisponible (CDN bloque / offline)
    // ---------------------------------------------------------------------
    self.addEventListener("install", (event) => {
        event.waitUntil(
            caches
                .open(CACHE_NAMES.precache)
                .then((cache) => cache.addAll(PRECACHE_URLS))
                .catch(() => {})
        );
    });

    self.addEventListener("fetch", (event) => {
        const { request } = event;
        if (request.method !== "GET") return;

        event.respondWith(
            fetch(request).catch(async () => {
                const cached = await caches.match(request);
                if (cached) return cached;
                if (request.destination === "document") {
                    const offline = await caches.match("/offline.html");
                    if (offline) return offline;
                }
                return Response.error();
            })
        );
    });
}

// -------------------------------------------------------------------------
// Activation : nettoie les anciens caches (versions precedentes + legacy)
// -------------------------------------------------------------------------
self.addEventListener("activate", (event) => {
    const validCacheNames = Object.values(CACHE_NAMES);
    event.waitUntil(
        (async () => {
            const keys = await caches.keys();
            await Promise.all(
                keys.map((key) => {
                    const isCurrent = validCacheNames.includes(key);
                    const isWorkboxCurrent = key.includes(VERSION);
                    const isLegacy = LEGACY_CACHES.includes(key);
                    if (isLegacy || (!isCurrent && !isWorkboxCurrent)) {
                        return caches.delete(key);
                    }
                    return Promise.resolve();
                })
            );
            await self.clients.claim();
        })()
    );
});

// -------------------------------------------------------------------------
// SKIP_WAITING : permet a la page de forcer l'activation de la nouvelle version
// -------------------------------------------------------------------------
self.addEventListener("message", (event) => {
    if (event.data && event.data.type === "SKIP_WAITING") {
        self.skipWaiting();
    }
});

// -------------------------------------------------------------------------
// Web Push (STUBS — completes en phase 6)
// -------------------------------------------------------------------------
self.addEventListener("push", (event) => {
    let payload = {};
    try {
        payload = event.data ? event.data.json() : {};
    } catch (e) {
        payload = {
            title: "KLASSCI",
            body: event.data ? event.data.text() : "Nouvelle notification",
        };
    }

    const title = payload.title || "KLASSCI";
    const options = {
        body: payload.body || "",
        icon: payload.icon || "/icons/icon-192.png",
        badge: payload.badge || "/icons/icon-192.png",
        data: { url: payload.url || "/dashboard" },
        tag: payload.tag,
    };

    event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener("notificationclick", (event) => {
    event.notification.close();
    const targetUrl =
        (event.notification.data && event.notification.data.url) || "/dashboard";

    event.waitUntil(
        (async () => {
            const allClients = await self.clients.matchAll({
                type: "window",
                includeUncontrolled: true,
            });
            for (const client of allClients) {
                if (client.url.includes(targetUrl) && "focus" in client) {
                    return client.focus();
                }
            }
            if (self.clients.openWindow) {
                return self.clients.openWindow(targetUrl);
            }
        })()
    );
});
