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
        toggle.setAttribute('aria-label', `${showPassword ? 'Sembunyikan' : 'Tampilkan'} password`);
        toggle.querySelector('[data-password-icon] use').setAttribute(
            'href',
            showPassword ? toggle.dataset.passwordHideIcon : toggle.dataset.passwordShowIcon,
        );
    });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeApplicationUi, { once: true });
} else {
    initializeApplicationUi();
}
