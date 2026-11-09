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
    const iosHelp = document.querySelectorAll('[data-pwa-ios-help]');
    const installStatus = document.getElementById('pwa-install-status');
    const updateCard = document.getElementById('pwa-update-card');
    const updateButton = document.getElementById('pwa-update-button');
    const updateStatus = document.getElementById('pwa-update-status');
    let waitingWorker = null;
    let installPrompt = null;
    let reloadAfterUpdate = false;

    if (!isStandalone() && isIos()) {
        iosHelp.forEach((element) => element.removeAttribute('hidden'));
    } else if (isStandalone() && installStatus) {
        installStatus.textContent = 'Jadwal Harian sudah terpasang di perangkat ini.';
    }

    window.addEventListener('beforeinstallprompt', (event) => {
        event.preventDefault();
        installPrompt = event;
        installButtons.forEach((button) => button.removeAttribute('hidden'));
        if (installStatus) {
            installStatus.textContent = 'Jadwal Harian siap dipasang di perangkat ini.';
        }
    });

    installButtons.forEach((button) => button.addEventListener('click', async () => {
        if (!installPrompt) {
            return;
        }

        try {
            const prompt = installPrompt;
            installPrompt = null;
            await prompt.prompt();
            const choice = await prompt.userChoice;
            if (installStatus) {
                installStatus.textContent = choice.outcome === 'accepted'
                    ? 'Jadwal Harian berhasil dipasang.'
                    : 'Pemasangan dibatalkan. Kamu bisa mencobanya lagi nanti.';
            }
            installButtons.forEach((installButton) => installButton.setAttribute('hidden', ''));
        } catch (error) {
            console.error('PWA installation prompt failed.', error);
            installButtons.forEach((installButton) => installButton.setAttribute('hidden', ''));
            if (installStatus) {
                installStatus.textContent = 'Pemasangan belum berhasil. Coba lagi dari browser.';
            }
        }
    }));

    window.addEventListener('appinstalled', () => {
        installPrompt = null;
        installButtons.forEach((button) => button.setAttribute('hidden', ''));
        iosHelp.forEach((element) => element.setAttribute('hidden', ''));
        if (installStatus) {
            installStatus.textContent = 'Jadwal Harian berhasil dipasang di perangkat ini.';
        }
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
        updateCard?.removeAttribute('hidden');
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
                updateButton.textContent = 'Menerapkan...';
                if (updateStatus) {
                    updateStatus.textContent = 'Pembaruan sedang diterapkan. Halaman akan dimuat kembali otomatis.';
                }
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
