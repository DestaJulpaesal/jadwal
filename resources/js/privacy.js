export function initializePrivacyUi() {
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
}
