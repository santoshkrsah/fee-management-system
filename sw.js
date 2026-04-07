/**
 * Service Worker for Fee Management System
 *
 * CACHING STRATEGY:
 *  - Static assets (CSS/JS/fonts from CDN and local):  Cache-first, background update
 *  - PHP pages & AJAX requests:                        Network-only, NEVER cached
 *
 * WHY: PHP pages are session-dependent. Caching them causes the service worker
 * to serve a stale/login-page response for authenticated URLs, making every
 * page click appear to log the user out.
 */

const CACHE_NAME = 'fee-management-static-v2';

// Only static, session-independent assets are cached
const STATIC_CACHE_URLS = [
  '/assets/css/style.css',
  '/assets/css/student.css',
  '/assets/js/script.js',
  '/assets/js/qrcode.min.js',
  'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css',
  'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js',
  'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'
];

// ── Install ──────────────────────────────────────────────────────────────────
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      return cache.addAll(STATIC_CACHE_URLS).catch(() => {
        // Non-fatal: continue even if some CDN assets fail to pre-cache
        return Promise.resolve();
      });
    })
  );
  self.skipWaiting();
});

// ── Activate: remove all old caches ──────────────────────────────────────────
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((names) =>
      Promise.all(
        names
          .filter((name) => name !== CACHE_NAME)
          .map((name) => caches.delete(name))
      )
    )
  );
  return self.clients.claim();
});

// ── Fetch: route by request type ─────────────────────────────────────────────
self.addEventListener('fetch', (event) => {
  const url = new URL(event.request.url);

  // 1. Only handle http(s)
  if (!url.protocol.startsWith('http')) return;

  // 2. PHP pages and AJAX → always network, never cache
  //    This covers every .php file, query strings, and POST requests
  if (
    event.request.method !== 'GET' ||
    url.pathname.endsWith('.php') ||
    url.search !== ''
  ) {
    // Let the browser handle it normally (no respondWith = pass-through)
    return;
  }

  // 3. Static assets → cache-first, background refresh
  event.respondWith(
    caches.match(event.request).then((cached) => {
      // Kick off a background network request to keep the cache fresh
      const networkFetch = fetch(event.request)
        .then((response) => {
          if (response && response.status === 200 && response.type !== 'opaque') {
            caches.open(CACHE_NAME).then((cache) => {
              cache.put(event.request, response.clone());
            });
          }
          return response;
        })
        .catch(() => null);

      // Return cache immediately if available, otherwise wait for network
      return cached || networkFetch;
    })
  );
});

// ── Handle messages from the page (e.g. cache-busting on logout) ─────────────
self.addEventListener('message', (event) => {
  if (event.data && event.data.action === 'clearCache') {
    event.waitUntil(
      caches.keys().then((names) =>
        Promise.all(names.map((name) => caches.delete(name)))
      )
    );
  }
  if (event.data && event.data.action === 'skipWaiting') {
    self.skipWaiting();
  }
});
