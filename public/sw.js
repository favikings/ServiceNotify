const STATIC_CACHE = 'servicenotify-static-v2';
const PRIVATE_PREFIX = 'servicenotify-private-v1-';
const STATE_CACHE = 'servicenotify-pwa-state-v1';
const ACTIVE_USER_URL = new URL('./__pwa_active_user__', self.registration.scope).href;
const OFFLINE_URL = new URL('./offline.html', self.registration.scope).href;
const STATIC_ASSETS = [
  './offline.html',
  './manifest.json',
  './assets/css/app.css',
  './assets/js/app.js',
  './assets/js/push.js',
  './assets/icons/icon-192.png',
  './assets/icons/icon-512.png',
  './assets/icons/icon-maskable-512.png',
  './assets/icons/apple-touch-icon.png'
];

self.addEventListener('install', (event) => {
  event.waitUntil(caches.open(STATIC_CACHE).then((cache) => cache.addAll(STATIC_ASSETS)));
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil((async () => {
    const names = await caches.keys();
    await Promise.all(names.map((name) => {
      const staleStatic = name.startsWith('servicenotify-static-') && name !== STATIC_CACHE;
      const staleState = name.startsWith('servicenotify-pwa-state-') && name !== STATE_CACHE;
      const stalePrivate = name.startsWith('servicenotify-private-') && !name.startsWith(PRIVATE_PREFIX);
      return staleStatic || staleState || stalePrivate ? caches.delete(name) : Promise.resolve(false);
    }));
    await self.clients.claim();
  })());
});

self.addEventListener('message', (event) => {
  const message = event.data || {};
  if (message.type === 'CLEAR_PRIVATE_CACHES') {
    event.waitUntil(clearPrivateCaches());
  }
});

self.addEventListener('fetch', (event) => {
  const request = event.request;
  if (request.method !== 'GET') return;

  const url = new URL(request.url);
  if (url.origin !== self.location.origin) return;

  if (request.mode === 'navigate') {
    if (isPrivateOfflineRoute(url.pathname)) {
      event.respondWith(privateNetworkFirst(request));
    } else {
      event.respondWith(fetch(request).catch(() => caches.match(OFFLINE_URL)));
    }
    return;
  }

  if (isStaticAsset(url.pathname)) {
    event.respondWith(cacheFirst(request));
  }
});

self.addEventListener('push', (event) => {
  let payload = {};
  try {
    payload = event.data ? event.data.json() : {};
  } catch (error) {
    payload = { notification: { body: event.data ? event.data.text() : '' } };
  }
  const notification = payload.notification || {};
  const title = notification.title || 'Service renewal reminder';
  const dashboardUrl = new URL('./dashboard.php', self.registration.scope).href;
  const requestedUrl = payload.fcmOptions?.link || payload.data?.url || dashboardUrl;
  const notificationUrl = sameOriginUrl(requestedUrl, dashboardUrl);
  event.waitUntil(self.registration.showNotification(title, {
    body: notification.body || 'A tracked service needs your attention.',
    icon: new URL('./assets/icons/icon-192.png', self.registration.scope).href,
    badge: new URL('./assets/icons/icon-192.png', self.registration.scope).href,
    data: { url: notificationUrl }
  }));
});

function sameOriginUrl(candidate, fallback) {
  try {
    const url = new URL(candidate, self.registration.scope);
    return url.origin === self.location.origin ? url.href : fallback;
  } catch (error) {
    return fallback;
  }
}

self.addEventListener('notificationclick', (event) => {
  event.notification.close();
  const targetUrl = event.notification.data?.url || new URL('./dashboard.php', self.registration.scope).href;
  event.waitUntil((async () => {
    const windows = await self.clients.matchAll({ type: 'window', includeUncontrolled: true });
    for (const client of windows) {
      if ('focus' in client && new URL(client.url).origin === self.location.origin) {
        if ('navigate' in client) await client.navigate(targetUrl);
        return client.focus();
      }
    }
    return self.clients.openWindow(targetUrl);
  })());
});

function isPrivateOfflineRoute(pathname) {
  return pathname.endsWith('/dashboard.php') || pathname.endsWith('/clients.php');
}

function isStaticAsset(pathname) {
  return pathname.includes('/assets/') || pathname.endsWith('/manifest.json') || pathname.endsWith('/offline.html');
}

async function privateNetworkFirst(request) {
  try {
    const response = await fetch(request);
    const privateKey = response.headers.get('X-ServiceNotify-PWA-Key');
    if (response.ok && privateKey && /^[a-f0-9]{24}$/.test(privateKey)) {
      const cacheName = PRIVATE_PREFIX + privateKey;
      const cache = await caches.open(cacheName);
      await cache.put(request, response.clone());
      const state = await caches.open(STATE_CACHE);
      await state.put(ACTIVE_USER_URL, new Response(privateKey));
    } else if (!privateKey) {
      await clearPrivateCaches();
    }
    return response;
  } catch (error) {
    const privateKey = await activePrivateKey();
    if (privateKey) {
      const cache = await caches.open(PRIVATE_PREFIX + privateKey);
      const cached = await cache.match(request, { ignoreSearch: true });
      if (cached) return cached;
    }
    return (await caches.match(OFFLINE_URL)) || Response.error();
  }
}

async function cacheFirst(request) {
  const cached = await caches.match(request);
  if (cached) return cached;
  const response = await fetch(request);
  if (response.ok) {
    const cache = await caches.open(STATIC_CACHE);
    await cache.put(request, response.clone());
  }
  return response;
}

async function activePrivateKey() {
  const state = await caches.open(STATE_CACHE);
  const response = await state.match(ACTIVE_USER_URL);
  return response ? response.text() : null;
}

async function clearPrivateCaches() {
  const names = await caches.keys();
  await Promise.all(names.filter((name) => name.startsWith('servicenotify-private-')).map((name) => caches.delete(name)));
  await caches.delete(STATE_CACHE);
}
