const CACHE_NAME = 'si-logistik-static-v1';
const OFFLINE_URL = '/offline.html';
const STATIC_ASSETS = [OFFLINE_URL, '/images/logo-sekolah.png', '/images/logo-sekolah-192.png'];

self.addEventListener('install', (event) => {
    event.waitUntil(caches.open(CACHE_NAME).then((cache) => cache.addAll(STATIC_ASSETS)).then(() => self.skipWaiting()));
});

self.addEventListener('activate', (event) => {
    event.waitUntil(caches.keys()
        .then((keys) => Promise.all(keys.filter((key) => key.startsWith('si-logistik-') && key !== CACHE_NAME).map((key) => caches.delete(key))))
        .then(() => self.clients.claim()));
});

self.addEventListener('fetch', (event) => {
    const request = event.request;
    const url = new URL(request.url);
    if (request.method !== 'GET' || url.origin !== self.location.origin) return;

    // Halaman berisi data akun selalu diambil dari server, tidak pernah disimpan ke cache.
    if (request.mode === 'navigate') {
        event.respondWith(fetch(request).catch(async () => (await caches.match(OFFLINE_URL)) || Response.error()));
        return;
    }

    // Cache hanya aset publik aplikasi. Foto barang dan respons endpoint tidak masuk cache.
    const isPublicAsset = url.pathname.startsWith('/build/assets/') || url.pathname.startsWith('/images/logo-sekolah');
    if (!isPublicAsset) return;
    event.respondWith(caches.match(request).then((cached) => {
        if (cached) return cached;
        return fetch(request).then((response) => {
            if (response.ok) caches.open(CACHE_NAME).then((cache) => cache.put(request, response.clone()));
            return response;
        });
    }));
});
