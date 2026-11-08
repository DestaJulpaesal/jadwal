import { handleOptimisticAction } from './optimistic-actions';
import { handleNavigationClick } from './nav';
import { handlePasswordToggle } from './password';
import { showErrorToast } from './toast';

const formSnapshot = (form) => JSON.stringify(Array.from(new FormData(form))
    .filter(([name, value]) => !/password/i.test(name) && (typeof value === 'string' || value instanceof File))
    .map(([name, value]) => [
        name,
        value instanceof File ? `${value.name}:${value.size}:${value.lastModified}` : value,
    ]));

const isDirty = (form) => form.dataset.dirtyGuard === 'true'
    && form.dataset.submitting !== 'true'
    && formSnapshot(form) !== form.dataset.dirtyBaseline;

const setButtonLabel = (button, value) => {
    const label = button.querySelector('[data-button-label]');

    if (label) {
        label.textContent = value;
    } else {
        button.textContent = value;
    }
};

const resetFormSubmission = (form) => {
    const button = form._submittingButton;

    if (button) {
        setButtonLabel(button, button.dataset.originalLabel ?? button.textContent.trim());
        button.disabled = button.dataset.wasDisabled === 'true';
        button.removeAttribute('aria-busy');
        delete button.dataset.originalLabel;
        delete button.dataset.wasDisabled;
        form._submittingButton = null;
    }

    if (form._submissionTimer) {
        window.clearTimeout(form._submissionTimer);
        form._submissionTimer = null;
    }

    form.removeAttribute('aria-busy');
    delete form.dataset.submitting;
};

const submitFeedback = (form, button) => {
    let feedback = form.querySelector('[data-submit-feedback]');

    if (!feedback) {
        feedback = document.createElement('div');
        feedback.className = 'form-submit-feedback';
        feedback.dataset.submitFeedback = '';
        feedback.setAttribute('role', 'alert');
        form.append(feedback);
    }

    feedback.replaceChildren();
    feedback.append(document.createTextNode('Koneksi belum merespons. Kirim ulang perubahan? '));

    const retry = document.createElement('button');
    retry.type = 'button';
    retry.dataset.submitRetry = '';
    retry.className = 'font-semibold underline';
    retry.textContent = 'Coba lagi';
    retry.addEventListener('click', () => {
        feedback.remove();
        form.requestSubmit(button?.isConnected ? button : undefined);
    }, { once: true });

    feedback.append(retry);
};

const makeDiscardDialog = () => {
    const dialog = document.createElement('dialog');
    dialog.className = 'form-discard-dialog';
    dialog.setAttribute('aria-labelledby', 'form-discard-title');

    const heading = document.createElement('h2');
    heading.id = 'form-discard-title';
    heading.className = 'text-lg font-semibold';
    heading.textContent = 'Buang perubahan?';

    const message = document.createElement('p');
    message.className = 'mt-2 text-sm text-muted';
    message.textContent = 'Perubahan yang belum disimpan akan hilang.';

    const actions = document.createElement('div');
    actions.className = 'mt-5 flex flex-col-reverse justify-end gap-2 sm:flex-row';

    const keep = document.createElement('button');
    keep.type = 'button';
    keep.className = 'button button--primary button--md';
    keep.textContent = 'Lanjut mengisi';
    keep.addEventListener('click', () => dialog.close());

    const discard = document.createElement('button');
    discard.type = 'button';
    discard.className = 'button button--danger button--md';
    discard.textContent = 'Buang perubahan';

    actions.append(keep, discard);
    dialog.append(heading, message, actions);
    document.body.append(dialog);

    return { dialog, discard };
};

const confirmDiscardChanges = (onDiscard) => {
    if (typeof HTMLDialogElement === 'undefined' || !HTMLDialogElement.prototype.showModal) {
        if (window.confirm('Buang perubahan? Perubahan yang belum disimpan akan hilang.')) {
            onDiscard();
        }

        return;
    }

    const { dialog, discard } = makeDiscardDialog();
    discard.addEventListener('click', () => {
        dialog.close();
        onDiscard();
    }, { once: true });
    dialog.addEventListener('close', () => dialog.remove(), { once: true });
    dialog.showModal();
};

window.isDirtyForm = isDirty;
window.confirmDiscardChanges = confirmDiscardChanges;

const formatDate = (date) => {
    const pad = (value) => String(value).padStart(2, '0');

    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
};

const dispatchFieldChange = (field) => {
    field.dispatchEvent(new Event('input', { bubbles: true }));
    field.dispatchEvent(new Event('change', { bubbles: true }));
};

const updateTextarea = (textarea) => {
    textarea.style.height = 'auto';
    textarea.style.height = `${textarea.scrollHeight}px`;
};

const updateConditionalFields = (form) => {
    form.querySelectorAll('[data-conditional-field]').forEach((container) => {
        const valueControl = form.elements.namedItem(container.dataset.conditionName);
        const selectedValue = valueControl?.value ?? '';
        const matches = container.dataset.conditionExcept
            ? selectedValue !== container.dataset.conditionExcept
            : selectedValue === container.dataset.conditionValue;
        const visible = matches || container.hasAttribute('data-condition-always');

        container.hidden = !visible;
        container.querySelectorAll('input, select, textarea').forEach((field) => {
            field.disabled = !visible;

            if (container.hasAttribute('data-condition-required')) {
                field.required = visible;
            }
        });
    });

    form.querySelectorAll('select[data-match-field]').forEach((field) => {
        const source = form.elements.namedItem(field.dataset.matchField)?.value ?? '';

        Array.from(field.options).forEach((option) => {
            if (!option.dataset.matchValue) {
                return;
            }

            option.disabled = option.dataset.matchValue !== source;

            if (option.disabled && option.selected) {
                field.value = '';
            }
        });
    });
};

const initializeForms = () => {
    document.querySelectorAll('form[data-dirty-guard]').forEach((form) => {
        form.dataset.dirtyBaseline = formSnapshot(form);
    });
    document.querySelectorAll('form').forEach(updateConditionalFields);

    document.querySelectorAll('textarea[data-autoresize]').forEach(updateTextarea);

    document.querySelectorAll('[data-password-strength-for]').forEach((meter) => {
        const field = document.getElementById(meter.dataset.passwordStrengthFor);
        const bar = meter.querySelector('[data-password-strength-bar]');
        const label = meter.querySelector('[data-password-strength-label]');

        if (!field || !bar || !label) {
            return;
        }

        const update = () => {
            const password = field.value;
            const score = password
                ? Math.min(4, Number(password.length >= 8)
                    + Number(password.length >= 12)
                    + Number(/[a-z]/.test(password) && /[A-Z]/.test(password))
                    + Number(/\d/.test(password))
                    + Number(/[^A-Za-z0-9]/.test(password)))
                : 0;
            const messages = [
                'Kata sandi belum diisi.',
                'Lemah — gunakan kata sandi yang lebih panjang.',
                'Cukup — tambahkan variasi karakter.',
                'Baik — gunakan kata sandi unik untuk akun ini.',
                'Kuat — kata sandi panjang dan beragam.',
            ];
            const colors = ['bg-canvas', 'bg-danger', 'bg-warning', 'bg-success', 'bg-success'];

            meter.setAttribute('aria-valuenow', String(score));
            bar.style.width = `${score * 25}%`;
            bar.className = `h-full rounded-full transition-all ${colors[score]}`;
            label.textContent = messages[score];
        };

        update();
        field.addEventListener('input', update);
    });

    document.querySelectorAll('[data-password-checklist]').forEach((list) => {
        const field = document.getElementById('password');
        if (!field) {
            return;
        }

        const update = () => {
            const checks = {
                length: field.value.length >= 8,
                mixed: /[a-z]/.test(field.value) && /[A-Z]/.test(field.value),
                number: /\d/.test(field.value),
            };
            list.querySelectorAll('[data-password-rule]').forEach((item) => {
                const passed = checks[item.dataset.passwordRule];
                item.textContent = `${passed ? '✓' : '○'} ${item.textContent.slice(2)}`;
                item.classList.toggle('text-success', passed);
            });
        };
        field.addEventListener('input', update);
        update();
    });

    document.querySelectorAll('[data-category-color-picker]').forEach((picker) => {
        const color = picker.querySelector('input[type="color"]');
        const preset = picker.querySelector('[data-color-preset]');
        const preview = picker.querySelector('[data-color-preview]');

        if (!color || !preset || !preview) {
            return;
        }

        const updatePreview = () => {
            const hex = color.value;
            const namedOption = Array.from(preset.options).find((option) => option.value.toLowerCase() === hex.toLowerCase());
            const customOption = Array.from(preset.options).find((option) => option.value === 'custom');
            const label = namedOption?.textContent.trim() ?? `Warna khusus ${hex.toUpperCase()}`;
            const channel = (start) => {
                const value = Number.parseInt(hex.slice(start, start + 2), 16) / 255;

                return value <= 0.04045 ? value / 12.92 : ((value + 0.055) / 1.055) ** 2.4;
            };
            const luminance = 0.2126 * channel(1) + 0.7152 * channel(3) + 0.0722 * channel(5);
            const darkContrast = (luminance + 0.05) / 0.05;
            const lightContrast = 1.05 / (luminance + 0.05);
            const useDarkText = darkContrast >= lightContrast;
            const contrast = Math.max(darkContrast, lightContrast);

            preset.value = namedOption?.value ?? 'custom';

            if (customOption) {
                customOption.textContent = `Warna khusus (${hex.toUpperCase()})`;
            }

            preview.style.backgroundColor = hex;
            preview.style.color = useDarkText ? '#000000' : '#FFFFFF';
            preview.textContent = `Contoh teks kategori · ${label} · kontras ${contrast.toFixed(1)}:1`;
        };

        preset.addEventListener('change', () => {
            if (preset.value !== 'custom') {
                color.value = preset.value;
            }

            updatePreview();
            color.dispatchEvent(new Event('input', { bubbles: true }));
            color.dispatchEvent(new Event('change', { bubbles: true }));
        });
        color.addEventListener('input', updatePreview);
        color.addEventListener('change', updatePreview);
        picker.closest('form')?.addEventListener('reset', () => window.setTimeout(updatePreview));
        updatePreview();
    });

    document.querySelectorAll('[data-disclosure]').forEach((disclosure) => {
        const trigger = disclosure.querySelector('[data-disclosure-trigger]');
        const content = disclosure.querySelector('[data-disclosure-content]');

        if (!trigger || !content) {
            return;
        }

        const containsError = Boolean(content.querySelector('[aria-invalid="true"], .field-error'));
        const initialOpen = disclosure.dataset.open === 'true' || containsError;
        content.hidden = !initialOpen;
        trigger.setAttribute('aria-expanded', String(initialOpen));

        trigger.addEventListener('click', () => {
            const opening = content.hidden;

            if (typeof content.animate !== 'function') {
                content.hidden = !opening;
            } else if (opening) {
                content.hidden = false;
                content.animate(
                    [{ opacity: 0, transform: 'translateY(-4px)' }, { opacity: 1, transform: 'translateY(0)' }],
                    { duration: 180, easing: 'ease-out' },
                );
            } else {
                const animation = content.animate(
                    [{ opacity: 1, transform: 'translateY(0)' }, { opacity: 0, transform: 'translateY(-4px)' }],
                    { duration: 140, easing: 'ease-in' },
                );
                animation.onfinish = () => {
                    content.hidden = true;
                };
            }

            trigger.setAttribute('aria-expanded', String(opening));
        });
    });

    document.querySelectorAll('[data-character-count-for]').forEach((counter) => {
        const field = document.getElementById(counter.dataset.characterCountFor);

        if (!field) {
            return;
        }

        const update = () => {
            counter.textContent = `${field.value.length}/${field.maxLength}`;
        };

        update();
        field.addEventListener('input', update);
    });

    const summaries = document.querySelectorAll('[data-error-summary]');
    summaries[0]?.focus();

    document.addEventListener('input', (event) => {
        const field = event.target;

        if (field instanceof HTMLTextAreaElement && field.hasAttribute('data-autoresize')) {
            updateTextarea(field);
        }

        if (field instanceof HTMLInputElement && field.hasAttribute('data-validate-on-blur')) {
            field.removeAttribute('aria-invalid');
        }
    });

    document.addEventListener('blur', (event) => {
        const field = event.target;

        if (!(field instanceof HTMLElement) || !field.hasAttribute('data-validate-on-blur')) {
            return;
        }

        field.setAttribute('aria-invalid', String(!field.checkValidity()));
    }, true);

    document.addEventListener('change', (event) => {
        const changedForm = event.target.closest('form');

        if (changedForm) {
            updateConditionalFields(changedForm);
        }

        const form = event.target.closest('form[data-auto-submit]');

        if (form) {
            const submitter = form.querySelector('[data-submit-default]') ?? undefined;
            form.requestSubmit(submitter);
        }
    });

    document.addEventListener('click', (event) => {
        const target = event.target instanceof Element ? event.target : null;
        handlePasswordToggle(target);
        const cancel = target?.closest('[data-cancel-form]');
        const guardedForm = cancel?.closest('form[data-dirty-guard]');

        if (guardedForm && isDirty(guardedForm)) {
            event.preventDefault();

            confirmDiscardChanges(() => window.location.assign(cancel.href));
        }

        const dateShortcut = target?.closest('[data-datetime-shortcut]');

        if (dateShortcut) {
            const field = document.getElementById(dateShortcut.dataset.target);

            if (!field) {
                return;
            }

            const now = new Date();
            const shortcut = dateShortcut.dataset.datetimeShortcut;

            if (shortcut === 'besok') {
                now.setDate(now.getDate() + 1);
            } else if (shortcut === 'akhir-pekan') {
                now.setDate(now.getDate() + ((6 - now.getDay() + 7) % 7 || 7));
            } else if (shortcut === 'minggu-depan') {
                now.setDate(now.getDate() + (8 - now.getDay()) % 7 || 7);
            }

            const date = formatDate(now);
            field.value = field.type === 'datetime-local'
                ? `${date}T${field.value.split('T')[1] || '09:00'}`
                : date;
            dispatchFieldChange(field);
        }

        const timeShortcut = target?.closest('[data-time-shortcut]');

        if (timeShortcut) {
            const field = document.getElementById(timeShortcut.dataset.target);

            if (field) {
                field.value = field.type === 'datetime-local'
                    ? `${field.value.split('T')[0] || formatDate(new Date())}T${timeShortcut.dataset.timeShortcut}`
                    : timeShortcut.dataset.timeShortcut;
                dispatchFieldChange(field);
            }
        }

        const durationShortcut = target?.closest('[data-duration-minutes]');

        if (durationShortcut) {
            const start = document.getElementById(durationShortcut.dataset.startTarget);
            const end = document.getElementById(durationShortcut.dataset.endTarget);

            if (!start?.value || !end) {
                return;
            }

            const startDate = new Date(start.value);
            startDate.setMinutes(startDate.getMinutes() + Number(durationShortcut.dataset.durationMinutes));
            end.value = start.type === 'datetime-local'
                ? `${formatDate(startDate)}T${String(startDate.getHours()).padStart(2, '0')}:${String(startDate.getMinutes()).padStart(2, '0')}`
                : `${String(startDate.getHours()).padStart(2, '0')}:${String(startDate.getMinutes()).padStart(2, '0')}`;
            dispatchFieldChange(end);
        }

        const errorLink = target?.closest('[data-error-summary] a[href^="#"]');

        if (errorLink) {
            const field = document.getElementById(decodeURIComponent(errorLink.hash.slice(1)));
            field?.focus();
            field?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }

        handleNavigationClick(event);
    });

    document.addEventListener('submit', (event) => {
        const form = event.target;

        if (!(form instanceof HTMLFormElement)) {
            return;
        }

        if (form.dataset.optimisticAction) {
            event.preventDefault();
            event.stopImmediatePropagation();
            void handleOptimisticAction(event, showErrorToast);

            return;
        }

        if (!navigator.onLine) {
            event.preventDefault();
            const networkBanner = document.querySelector('[data-network-banner]');
            if (networkBanner) {
                networkBanner.hidden = false;
            }

            return;
        }

        if (form.dataset.submitting === 'true') {
            event.preventDefault();

            return;
        }

        if (!form.checkValidity()) {
            event.preventDefault();
            form.reportValidity();
            form.querySelector(':invalid')?.focus();

            return;
        }

        form.dataset.submitting = 'true';
        form.setAttribute('aria-busy', 'true');

        const button = event.submitter instanceof HTMLButtonElement
            ? event.submitter
            : form.querySelector('button[type="submit"]:not([disabled])');

        if (!button) {
            return;
        }

        const label = button.querySelector('[data-button-label]');
        button.dataset.originalLabel = label?.textContent.trim() || button.textContent.trim();
        button.dataset.wasDisabled = String(button.disabled);
        button.disabled = true;
        button.setAttribute('aria-busy', 'true');
        form._submittingButton = button;

        if (button.dataset.loadingText) {
            setButtonLabel(button, button.dataset.loadingText);
        }

        form._submissionTimer = window.setTimeout(() => {
            resetFormSubmission(form);
            submitFeedback(form, button);
        }, 15000);
    });

    window.addEventListener('beforeunload', (event) => {
        const dirtyForm = Array.from(document.querySelectorAll('form[data-dirty-guard]')).find(isDirty);

        if (dirtyForm) {
            event.preventDefault();
            event.returnValue = '';
        }
    });

    window.addEventListener('pageshow', (event) => {
        if (event.persisted) {
            document.querySelectorAll('form').forEach(resetFormSubmission);
        }
    });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeForms, { once: true });
} else {
    initializeForms();
}
