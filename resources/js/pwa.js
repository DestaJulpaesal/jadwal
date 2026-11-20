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

const base64UrlToBytes = (value) => {
    const padding = '='.repeat((4 - (value.length % 4)) % 4);
    const base64 = (value + padding).replace(/-/g, '+').replace(/_/g, '/');

    return Uint8Array.from(atob(base64), (character) => character.charCodeAt(0));
};

const initializePushSettings = () => {
    const settings = document.querySelector('[data-push-settings]');
    if (!settings) {
        return;
    }

    const enableButton = settings.querySelector('[data-push-enable]');
    const disableButton = settings.querySelector('[data-push-disable]');
    const status = settings.querySelector('[data-push-status]');
    const supported = 'Notification' in window
        && 'serviceWorker' in navigator
        && 'PushManager' in window;

    if (!supported) {
        enableButton.disabled = true;
        status.textContent = settings.dataset.messageUnsupported;

        return;
    }

    const updateSubscriptionState = async () => {
        const registration = await navigator.serviceWorker.ready;
        const subscription = await registration.pushManager.getSubscription();
        enableButton.hidden = Boolean(subscription);
        disableButton.hidden = !subscription;
        status.textContent = subscription
            ? settings.dataset.messageActive
            : settings.dataset.messageInactive;
    };

    updateSubscriptionState().catch((error) => {
        console.error('PWA push subscription state could not be loaded.', error);
        status.textContent = settings.dataset.messageStatusUnavailable;
    });

    enableButton.addEventListener('click', async () => {
        try {
            const permission = await Notification.requestPermission();
            if (permission !== 'granted') {
                status.textContent = permission === 'denied'
                    ? settings.dataset.messagePermissionDenied
                    : settings.dataset.messagePermissionPending;

                return;
            }

            const registration = await navigator.serviceWorker.ready;
            const subscription = await registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: base64UrlToBytes(settings.dataset.vapidPublicKey),
            });
            const response = await fetch(settings.dataset.storeUrl, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': settings.dataset.csrfToken,
                },
                body: JSON.stringify(subscription.toJSON()),
            });
            if (!response.ok) {
                throw new Error(`Push subscription could not be saved (${response.status}).`);
            }

            await updateSubscriptionState();
            status.textContent = settings.dataset.messageEnabled;
        } catch (error) {
            console.error('PWA push subscription could not be enabled.', error);
            status.textContent = settings.dataset.messageFailed;
        }
    });

    disableButton.addEventListener('click', async () => {
        try {
            const registration = await navigator.serviceWorker.ready;
            const subscription = await registration.pushManager.getSubscription();
            if (subscription) {
                const response = await fetch(settings.dataset.deleteUrl, {
                    method: 'DELETE',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': settings.dataset.csrfToken,
                    },
                    body: JSON.stringify({ endpoint: subscription.endpoint }),
                });
                if (!response.ok) {
                    throw new Error(`Push subscription could not be removed (${response.status}).`);
                }

                await subscription.unsubscribe();
            }

            await updateSubscriptionState();
            status.textContent = settings.dataset.messageDisabled;
        } catch (error) {
            console.error('PWA push subscription could not be disabled.', error);
            status.textContent = settings.dataset.messageFailed;
        }
    });
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
    initializePushSettings();

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
