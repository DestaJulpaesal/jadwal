const isIos = () => /iPad|iPhone|iPod/.test(navigator.userAgent)
    || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);

const isStandalone = () => window.matchMedia('(display-mode: standalone)').matches
    || navigator.standalone === true;

const canRegisterServiceWorker = () => {
    const isLocalhost = ['localhost', '127.0.0.1', '[::1]'].includes(window.location.hostname);

    return 'serviceWorker' in navigator
        && (window.isSecureContext || isLocalhost)
        && !import.meta.env.DEV;
};

export const initializePwa = () => {
    const installButtons = document.querySelectorAll('[data-pwa-install]');
    const iosHelp = document.querySelector('[data-pwa-ios-help]');
    const updateBanner = document.getElementById('pwa-update-banner');
    const updateButton = document.getElementById('pwa-update-button');
    let waitingWorker = null;
    let installPrompt = null;
    let reloadAfterUpdate = false;

    if (!isStandalone() && isIos()) {
        iosHelp?.removeAttribute('hidden');
    }

    window.addEventListener('beforeinstallprompt', (event) => {
        event.preventDefault();
        installPrompt = event;
        installButtons.forEach((button) => button.removeAttribute('hidden'));
    });

    installButtons.forEach((button) => button.addEventListener('click', async () => {
        if (!installPrompt) {
            return;
        }

        await installPrompt.prompt();
        installPrompt = null;
        installButtons.forEach((installButton) => installButton.setAttribute('hidden', ''));
    }));

    window.addEventListener('appinstalled', () => {
        installPrompt = null;
        installButtons.forEach((button) => button.setAttribute('hidden', ''));
        iosHelp?.setAttribute('hidden', '');
    });

    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || !/\/logout\/?$/.test(new URL(form.action).pathname)) {
            return;
        }

        navigator.serviceWorker?.controller?.postMessage({ type: 'CLEAR_DYNAMIC_CACHES' });
    });

    if (!canRegisterServiceWorker()) {
        return;
    }

    const showUpdate = (worker) => {
        waitingWorker = worker;
        updateBanner?.removeAttribute('hidden');
    };

    navigator.serviceWorker.register('/sw.js', { scope: '/' })
        .then((registration) => {
            if (registration.waiting && navigator.serviceWorker.controller) {
                showUpdate(registration.waiting);
            }

            registration.addEventListener('updatefound', () => {
                const installingWorker = registration.installing;
                installingWorker?.addEventListener('statechange', () => {
                    if (installingWorker.state === 'installed' && navigator.serviceWorker.controller) {
                        showUpdate(installingWorker);
                    }
                });
            });

            updateButton?.addEventListener('click', () => {
                if (!waitingWorker) {
                    return;
                }

                reloadAfterUpdate = true;
                waitingWorker.postMessage({ type: 'SKIP_WAITING' });
                updateButton.setAttribute('disabled', '');
                updateButton.textContent = 'Memuat...';
            });

            navigator.serviceWorker.addEventListener('controllerchange', () => {
                if (reloadAfterUpdate) {
                    window.location.reload();
                }
            });
        })
        .catch((error) => {
            console.error('PWA service worker registration failed.', error);
        });
};
