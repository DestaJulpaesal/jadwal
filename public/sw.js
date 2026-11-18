const CACHE_PREFIX = 'jadwal-static-';
const CACHE_METADATA_NAME = 'jadwal-cache-metadata';
const CACHE_VERSION_KEY = '/__jadwal-static-version__';
const CACHE_PENDING_VERSION_KEY = '/__jadwal-pending-static-version__';
const MANIFEST_URL = '/build/manifest.json';
const HASHED_ASSET = /^\/build\/assets\/.+-[A-Za-z0-9_-]{8,}\.[A-Za-z0-9]+$/;
const STATIC_FILES = ['/offline.html'];
let activeCacheVersion = null;

const loadManifest = async () => {
    const response = await fetch(MANIFEST_URL, { cache: 'no-store' });
    if (!response.ok) {
        throw new Error('Vite manifest could not be loaded for the offline cache.');
    }

    const bytes = await response.arrayBuffer();
    const manifest = JSON.parse(new TextDecoder().decode(bytes));
    const digest = await crypto.subtle.digest('SHA-256', bytes);
    const version = [...new Uint8Array(digest)]
        .map((byte) => byte.toString(16).padStart(2, '0'))
        .join('');

    return { manifest, version };
};

const readActiveCacheVersion = async () => {
    if (activeCacheVersion) {
        return activeCacheVersion;
    }

    const metadata = await caches.open(CACHE_METADATA_NAME);
    const response = await metadata.match(CACHE_VERSION_KEY);
    activeCacheVersion = response ? await response.text() : null;

    return activeCacheVersion;
};

const collectHashedAssets = (manifest) => {
    const assets = new Set(STATIC_FILES);
    const visited = new Set();
    const visit = (entry) => {
        if (visited.has(entry)) {
            return;
        }
        visited.add(entry);

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
        const { manifest, version } = await loadManifest();
        const cache = await caches.open(`${CACHE_PREFIX}${version}`);
        const assets = collectHashedAssets(manifest);
        await cache.addAll(assets);
        const offlinePage = await cache.match('/offline.html');
        if (!offlinePage) {
            throw new Error('The offline fallback page was not cached.');
        }
        await cache.put('/offline', offlinePage);
        const metadata = await caches.open(CACHE_METADATA_NAME);
        await metadata.put(CACHE_PENDING_VERSION_KEY, new Response(version));
    })());
});

self.addEventListener('activate', (event) => {
    event.waitUntil((async () => {
        const metadata = await caches.open(CACHE_METADATA_NAME);
        const pendingVersion = await metadata.match(CACHE_PENDING_VERSION_KEY);
        const version = pendingVersion
            ? await pendingVersion.text()
            : (await loadManifest()).version;
        const currentCacheName = `${CACHE_PREFIX}${version}`;
        const cacheNames = await caches.keys();
        await metadata.put(CACHE_VERSION_KEY, new Response(version));
        await metadata.delete(CACHE_PENDING_VERSION_KEY);
        activeCacheVersion = version;
        await Promise.all(cacheNames
            .filter((name) => name.startsWith(CACHE_PREFIX) && name !== currentCacheName)
            .map((name) => caches.delete(name)));
        await self.clients.claim();
    })());
});

self.addEventListener('message', (event) => {
    if (event.data?.type === 'SKIP_WAITING') {
        self.skipWaiting();
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
            const version = await readActiveCacheVersion();
            if (!version) {
                return Response.error();
            }

            const cache = await caches.open(`${CACHE_PREFIX}${version}`);
            return (await cache.match('/offline')) || Response.error();
        }));
        return;
    }

    if (HASHED_ASSET.test(url.pathname)) {
        event.respondWith((async () => {
            const version = await readActiveCacheVersion();
            if (version) {
                const cache = await caches.open(`${CACHE_PREFIX}${version}`);
                const cached = await cache.match(request);
                if (cached) {
                    return cached;
                }
            }

            return fetch(request);
        })());
    }
});
