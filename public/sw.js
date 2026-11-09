const CACHE_PREFIX = 'jadwal-static-';
const CACHE_VERSION = 'v1';
const CACHE_NAME = `${CACHE_PREFIX}${CACHE_VERSION}`;
const HASHED_ASSET = /^\/build\/assets\/.+-[A-Za-z0-9_-]{8,}\.[A-Za-z0-9]+$/;
const STATIC_FILES = ['/offline.html'];

const collectHashedAssets = (manifest) => {
    const assets = new Set(STATIC_FILES);
    const visit = (entry) => {
        if (entry.file) {
            const path = `/${entry.file}`;
            if (HASHED_ASSET.test(path)) {
                assets.add(path);
            }
        }

        (entry.css || []).forEach((path) => {
            const asset = `/${path}`;
            if (HASHED_ASSET.test(asset)) {
                assets.add(asset);
            }
        });
        (entry.assets || []).forEach((path) => {
            const asset = `/${path}`;
            if (HASHED_ASSET.test(asset)) {
                assets.add(asset);
            }
        });
        (entry.imports || []).forEach((key) => {
            if (manifest[key]) {
                visit(manifest[key]);
            }
        });
    };

    Object.values(manifest).forEach(visit);

    return [...assets];
};

self.addEventListener('install', (event) => {
    event.waitUntil((async () => {
        const manifestResponse = await fetch('/build/manifest.json', { cache: 'no-store' });
        if (!manifestResponse.ok) {
            throw new Error('Vite manifest could not be loaded for the offline cache.');
        }

        const manifest = await manifestResponse.json();
        const cache = await caches.open(CACHE_NAME);
        const assets = collectHashedAssets(manifest);
        await cache.addAll(assets);
        const offlinePage = await cache.match('/offline.html');
        if (!offlinePage) {
            throw new Error('The offline fallback page was not cached.');
        }
        await cache.put('/offline', offlinePage);
    })());
});

self.addEventListener('activate', (event) => {
    event.waitUntil((async () => {
        const cacheNames = await caches.keys();
        await Promise.all(cacheNames
            .filter((name) => name.startsWith(CACHE_PREFIX) && name !== CACHE_NAME)
            .map((name) => caches.delete(name)));
        await self.clients.claim();
    })());
});

self.addEventListener('message', (event) => {
    if (event.data?.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }

    if (event.data?.type === 'CLEAR_DYNAMIC_CACHES') {
        event.waitUntil(caches.keys().then((cacheNames) => Promise.all(
            cacheNames
                .filter((name) => name.startsWith('jadwal-dynamic-'))
                .map((name) => caches.delete(name))
        )));
    }
});

self.addEventListener('fetch', (event) => {
    const request = event.request;
    const url = new URL(request.url);

    if (request.method !== 'GET' || url.origin !== self.location.origin) {
        return;
    }

    if (request.mode === 'navigate') {
        event.respondWith(fetch(request).catch(async () => {
            const cache = await caches.open(CACHE_NAME);
            return (await cache.match('/offline')) || Response.error();
        }));
        return;
    }

    if (HASHED_ASSET.test(url.pathname)) {
        event.respondWith(caches.open(CACHE_NAME).then((cache) => cache.match(request).then((cached) => cached || fetch(request))));
    }
});
