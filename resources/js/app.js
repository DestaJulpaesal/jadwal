import './bootstrap';
import '@fontsource/figtree/400.css';
import '@fontsource/figtree/500.css';
import '@fontsource/figtree/600.css';
import '@fontsource/figtree/700.css';
import './forms';

import Alpine from 'alpinejs';

window.Alpine = Alpine;
window.rupiahInput = (initial = '') => ({
    display: '',
    raw: '',
    init() {
        const value = String(initial ?? '');
        const negative = value.startsWith('-');
        const digits = value.replace(/\D/g, '');
        this.raw = negative && digits ? `-${digits}` : digits;
        this.display = `${negative && digits ? '-' : ''}${digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.')}`;
    },
    get preview() {
        return this.raw ? `Rp ${this.display}` : 'Masukkan nominal dalam Rupiah';
    },
    format(event) {
        const value = event.target.value;
        const negative = value.trim().startsWith('-');
        const digits = value.replace(/\D/g, '');
        this.raw = negative && digits ? `-${digits}` : digits;
        this.display = `${negative && digits ? '-' : ''}${digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.')}`;
    },
});

Alpine.start();

const initializeApplicationUi = () => {
    const toasts = document.querySelectorAll('[data-toast]');
    toasts.forEach((toast, index) => {
        if (index < toasts.length - 3) {
            toast.remove();
        }
    });

    const networkBanner = document.querySelector('[data-network-banner]');
    const updateNetworkState = () => {
        if (networkBanner) {
            networkBanner.hidden = navigator.onLine;
        }
    };

    updateNetworkState();
    window.addEventListener('online', updateNetworkState);
    window.addEventListener('offline', updateNetworkState);

    document.querySelectorAll('[data-login-throttle]').forEach((message) => {
        const form = message.closest('form');
        const button = form?.querySelector('button[type="submit"]');
        let remaining = Number(message.dataset.throttleSeconds);

        if (!button || !Number.isFinite(remaining) || remaining < 1) {
            return;
        }

        button.disabled = true;
        const update = () => {
            if (remaining <= 0) {
                message.textContent = 'Silakan coba lagi.';
                button.disabled = false;
                return;
            }

            const minutes = Math.floor(remaining / 60);
            const seconds = String(remaining % 60).padStart(2, '0');
            message.textContent = `Terlalu banyak percobaan masuk. Coba lagi dalam ${minutes}:${seconds}.`;
            remaining -= 1;
            window.setTimeout(update, 1000);
        };
        update();
    });

    document.querySelectorAll('[data-reset-cooldown]').forEach((button) => {
        let remaining = Number(button.dataset.resetCooldown);
        if (!Number.isFinite(remaining) || remaining < 1) {
            return;
        }
        button.disabled = true;
        const update = () => {
            if (remaining <= 0) {
                button.disabled = false;
                button.textContent = 'Kirim ulang tautan pengaturan ulang';
                return;
            }
            button.textContent = `Kirim ulang dalam ${remaining} detik`;
            remaining -= 1;
            window.setTimeout(update, 1000);
        };
        update();
    });

    document.querySelectorAll('[data-retry-countdown]').forEach((label) => {
        let remaining = Number(label.dataset.retrySeconds);
        if (!Number.isFinite(remaining) || remaining < 1) {
            return;
        }
        const update = () => {
            if (remaining <= 0) {
                label.textContent = 'Kamu bisa mencoba lagi sekarang.';
                return;
            }
            label.textContent = `Coba lagi dalam ${remaining} detik.`;
            remaining -= 1;
            window.setTimeout(update, 1000);
        };
        update();
    });

    let privacyHidden = document.body.dataset.privateDefault === 'true';

    const updatePrivacy = () => {
        document.querySelectorAll('[data-private]').forEach((element) => {
            if (!Object.hasOwn(element.dataset, 'privateValue')) {
                element.dataset.privateValue = element.textContent.trim();
            }

            element.textContent = privacyHidden ? 'Rp •••••' : element.dataset.privateValue;
        });
        document.querySelectorAll('[data-privacy-toggle]').forEach((button) => {
            button.setAttribute('aria-pressed', String(privacyHidden));
            button.setAttribute('aria-label', privacyHidden ? 'Tampilkan nominal' : 'Sembunyikan nominal');
            button.title = privacyHidden ? 'Tampilkan nominal' : 'Sembunyikan nominal';
            button.querySelector('[data-private-show]')?.classList.toggle('hidden', privacyHidden);
            button.querySelector('[data-private-hide]')?.classList.toggle('hidden', !privacyHidden);
        });
    };

    updatePrivacy();
    document.querySelectorAll('[data-privacy-toggle]').forEach((button) => {
        button.addEventListener('click', () => {
            privacyHidden = !privacyHidden;
            updatePrivacy();
        });
    });

    document.addEventListener('click', (event) => {
        const toggle = event.target.closest?.('[data-password-toggle]');

        if (!toggle) {
            return;
        }

        const input = toggle.parentElement.querySelector('[data-password-field]');

        if (!input) {
            return;
        }

        const showPassword = input.type === 'password';
        input.type = showPassword ? 'text' : 'password';
        toggle.querySelector('[data-password-label]').textContent = showPassword ? 'Sembunyikan' : 'Tampilkan';
        toggle.setAttribute('aria-label', `${showPassword ? 'Sembunyikan' : 'Tampilkan'} kata sandi`);
        toggle.querySelector('[data-password-icon] use').setAttribute(
            'href',
            showPassword ? toggle.dataset.passwordHideIcon : toggle.dataset.passwordShowIcon,
        );
    });

    document.querySelectorAll('[data-caps-lock]').forEach((input) => {
        const hint = document.querySelector('[data-caps-lock-hint]');
        if (!hint) {
            return;
        }

        const updateCapsLockHint = (event) => {
            hint.hidden = !event.getModifierState?.('CapsLock');
        };
        input.addEventListener('keydown', updateCapsLockHint);
        input.addEventListener('keyup', updateCapsLockHint);
        input.addEventListener('blur', () => {
            hint.hidden = true;
        });
    });

    document.addEventListener('submit', (event) => {
        if (navigator.onLine) {
            return;
        }

        event.preventDefault();
        if (networkBanner) {
            networkBanner.hidden = false;
        }
    }, true);
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeApplicationUi, { once: true });
} else {
    initializeApplicationUi();
}
