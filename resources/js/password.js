export function initializePasswordUi() {
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
}

export function handlePasswordToggle(target) {
    const toggle = target instanceof Element ? target.closest('[data-password-toggle]') : null;
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
}
