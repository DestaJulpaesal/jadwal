export function initializePrivacyUi() {
    const root = document.documentElement;
    const storageKey = 'jadwal:hide-financial-amounts';
    let privacyHidden = root.dataset.privacyHidden === 'true';

    const updatePrivacy = () => {
        root.dataset.privacyHidden = String(privacyHidden);
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
        root.dataset.privacyReady = 'true';
    };

    updatePrivacy();
    document.querySelectorAll('[data-privacy-toggle]').forEach((button) => {
        button.addEventListener('click', () => {
            privacyHidden = !privacyHidden;
            try {
                sessionStorage.setItem(storageKey, String(privacyHidden));
            } catch (error) {
                console.error('Could not save the financial privacy preference.', error);
            }
            updatePrivacy();
        });
    });
}
