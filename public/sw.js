const CACHE_NAME = 'tenace-pwa-v1.0.4';

self.addEventListener('push', (event) => {
  let payload = {};
  try {
    payload = event.data ? event.data.json() : {};
  } catch (error) {
    payload = {title: 'Nouvelle course disponible', body: 'Ouvre TENACOS pour consulter les livraisons.'};
  }

  const title = payload.title || 'Nouvelle course disponible';
  const options = {
    body: payload.body || 'Une nouvelle livraison est disponible.',
    icon: '/icons/icon-192x192.png',
    badge: '/icons/icon-192x192.png',
    tag: payload.tag || 'tenacos-course',
    renotify: false,
    data: {url: payload.url || '/livrable/list'}
  };
  event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', (event) => {
  event.notification.close();
  const requestedUrl = event.notification.data && event.notification.data.url;
  let target = new URL('/livrable/list', self.location.origin);
  try {
    const candidate = new URL(requestedUrl || '/livrable/list', self.location.origin);
    if (candidate.origin === self.location.origin) target = candidate;
  } catch (error) {}

  event.waitUntil(clients.matchAll({type: 'window', includeUncontrolled: true}).then((windowClients) => {
    for (const client of windowClients) {
      if (new URL(client.url).origin === self.location.origin && 'focus' in client) {
        client.navigate(target.href);
        return client.focus();
      }
    }
    return clients.openWindow(target.href);
  }));
});

const PRECACHE_ASSETS = [
  '/icons/icon-192x192.png',
  '/icons/icon-512x512.png',
  '/assets/images/tena.png',
  '/favicon.ico',
];

// Install Event
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      return cache.addAll(PRECACHE_ASSETS).catch((err) => {
        console.warn('[PWA SW] Pre-caching failed for some assets:', err);
      });
    })
  );
});

// Activate Event & Clean Old Caches
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames
          .filter((name) => name !== CACHE_NAME)
          .map((name) => caches.delete(name))
      );
    }).then(() => self.clients.claim())
  );
});

// Listen for SKIP_WAITING from client update prompt
self.addEventListener('message', (event) => {
  if (event.data && event.data.type === 'SKIP_WAITING') {
    self.skipWaiting();
  }
});

// Fetch Event
self.addEventListener('fetch', (event) => {
  const request = event.request;

  // Only handle GET requests
  if (request.method !== 'GET') {
    return;
  }

  const url = new URL(request.url);

  // Never serve or store a cached login page: it must always reflect the
  // current server-side theme and authentication state.
  if (request.mode === 'navigate' && (url.pathname === '/' || url.pathname === '/login')) {
    event.respondWith(fetch(request, { cache: 'no-store' }));
    return;
  }

  // Bypass Livewire, API, and admin action routes to guarantee live, fresh transactions
  if (
    url.pathname.startsWith('/livewire') ||
    url.pathname.includes('livewire') ||
    request.headers.get('X-Livewire')
  ) {
    return;
  }

  // 1. Navigation requests (HTML pages): Network-First
  if (request.mode === 'navigate') {
    event.respondWith(
      fetch(request)
        .then((response) => {
          if (response && response.status === 200) {
            const responseClone = response.clone();
            caches.open(CACHE_NAME).then((cache) => {
              cache.put(request, responseClone);
            });
          }
          return response;
        })
        .catch(() => {
          return caches.match(request).then((cachedResponse) => {
            if (cachedResponse) {
              return cachedResponse;
            }
            return new Response(
              '<div style="text-align:center;padding:50px;font-family:sans-serif;"><h2>Mode hors-ligne</h2><p>Vérifiez votre connexion internet pour accéder à cette page.</p></div>',
              { headers: { 'Content-Type': 'text/html; charset=utf-8' } }
            );
          });
        })
    );
    return;
  }

  // 2. Static Assets (CSS, JS, Images, Fonts): Stale-While-Revalidate
  const isStaticAsset =
    url.pathname.startsWith('/assets/') ||
    url.pathname.startsWith('/icons/') ||
    url.pathname.startsWith('/image/') ||
    url.pathname.endsWith('.css') ||
    url.pathname.endsWith('.js') ||
    url.pathname.endsWith('.png') ||
    url.pathname.endsWith('.jpg') ||
    url.pathname.endsWith('.svg') ||
    url.pathname.endsWith('.woff2') ||
    url.pathname.endsWith('.woff');

  if (isStaticAsset) {
    event.respondWith(
      caches.match(request).then((cachedResponse) => {
        const fetchPromise = fetch(request)
          .then((networkResponse) => {
            if (networkResponse && networkResponse.status === 200) {
              const clone = networkResponse.clone();
              caches.open(CACHE_NAME).then((cache) => {
                cache.put(request, clone);
              });
            }
            return networkResponse;
          })
          .catch(() => cachedResponse);

        return cachedResponse || fetchPromise;
      })
    );
    return;
  }

  // Default: Network with fallback to cache
  event.respondWith(
    fetch(request).catch(() => caches.match(request))
  );
});
