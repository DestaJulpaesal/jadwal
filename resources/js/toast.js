export function initializeToastQueue() {
    const toastQueue = [];
    const activeToasts = new Set();
    const maximumActiveToasts = 3;

    const dismissToast = (toast) => {
        window.clearTimeout(toast.toastTimer);
        toast.hidden = true;
        toast.style.display = 'none';
        toast.removeAttribute('aria-hidden');
        activeToasts.delete(toast);
        advanceToastQueue();
    };

    const startToastTimer = (toast) => {
        window.clearTimeout(toast.toastTimer);
        if (toast.toastPaused || !activeToasts.has(toast)) {
            return;
        }

        toast.toastDeadline = Date.now() + toast.toastRemaining;
        toast.toastTimer = window.setTimeout(
            () => dismissToast(toast),
            toast.toastRemaining,
        );
    };

    const pauseToastTimer = (toast) => {
        if (toast.toastPaused) {
            return;
        }

        toast.toastPaused = true;
        toast.toastRemaining = Math.max(0, toast.toastDeadline - Date.now());
        window.clearTimeout(toast.toastTimer);
    };

    const resumeToastTimer = (toast) => {
        if (!toast.toastPaused) {
            return;
        }

        toast.toastPaused = false;
        startToastTimer(toast);
    };

    const activateToast = (toast) => {
        const duration = Number(toast.dataset.toastDuration);
        toast.toastRemaining = Number.isFinite(duration) && duration > 0 ? duration : 6000;
        toast.toastPaused = false;
        toast.hidden = false;
        toast.style.display = '';
        toast.removeAttribute('aria-hidden');
        activeToasts.add(toast);
        startToastTimer(toast);
    };

    function advanceToastQueue() {
        while (activeToasts.size < maximumActiveToasts && toastQueue.length > 0) {
            activateToast(toastQueue.shift());
        }
    }

    const enqueueToast = (toast) => {
        toast.hidden = true;
        toast.style.display = 'none';
        toast.setAttribute('aria-hidden', 'true');
        toastQueue.push(toast);
        advanceToastQueue();
    };

    const registerToast = (toast) => {
        if (toast.dataset.toastManaged === 'true') {
            return;
        }

        toast.dataset.toastManaged = 'true';
        toast.querySelector('[data-toast-dismiss]')?.addEventListener('click', () => dismissToast(toast));
        toast.addEventListener('pointerenter', () => {
            toast.toastPointerInside = true;
            pauseToastTimer(toast);
        });
        toast.addEventListener('pointerleave', () => {
            toast.toastPointerInside = false;
            if (!toast.toastFocused && !toast.toastPointerDown) {
                resumeToastTimer(toast);
            }
        });
        toast.addEventListener('pointerdown', () => {
            toast.toastPointerDown = true;
            pauseToastTimer(toast);
        });
        toast.addEventListener('pointerup', () => {
            toast.toastPointerDown = false;
            if (!toast.toastPointerInside && !toast.toastFocused) {
                resumeToastTimer(toast);
            }
        });
        toast.addEventListener('focusin', () => {
            toast.toastFocused = true;
            pauseToastTimer(toast);
        });
        toast.addEventListener('focusout', (event) => {
            if (!toast.contains(event.relatedTarget)) {
                toast.toastFocused = false;
                if (!toast.toastPointerInside && !toast.toastPointerDown) {
                    resumeToastTimer(toast);
                }
            }
        });
        enqueueToast(toast);
    };

    document.querySelectorAll('[data-toast]').forEach(registerToast);

    const toastObserver = new MutationObserver((records) => {
        records.forEach((record) => {
            record.addedNodes.forEach((node) => {
                if (!(node instanceof Element)) {
                    return;
                }

                if (node.matches('[data-toast]')) {
                    registerToast(node);
                }
                node.querySelectorAll('[data-toast]').forEach(registerToast);
            });
        });
    });
    toastObserver.observe(document.body, { childList: true, subtree: true });
}

export function showErrorToast(message) {
    const region = document.querySelector('[data-toast-region]');
    if (!region) {
        console.error('Unable to display error notification: toast region is missing.', message);
        return;
    }

    const toast = document.createElement('div');
    toast.dataset.toast = '';
    toast.dataset.toastDuration = '6000';
    toast.className = 'pointer-events-auto flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-danger shadow-card';
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'assertive');

    const icon = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    icon.setAttribute('class', 'h-5 w-5 shrink-0');
    icon.setAttribute('aria-hidden', 'true');
    const use = document.createElementNS('http://www.w3.org/2000/svg', 'use');
    use.setAttribute('href', '/icons/sprite.svg#icon-galat');
    icon.append(use);

    const text = document.createElement('p');
    text.className = 'flex-1';
    text.textContent = message;

    const dismiss = document.createElement('button');
    dismiss.type = 'button';
    dismiss.dataset.toastDismiss = '';
    dismiss.className = 'shrink-0';
    dismiss.setAttribute('aria-label', 'Tutup pemberitahuan');
    dismiss.title = 'Tutup pemberitahuan';
    dismiss.textContent = '×';
    toast.append(icon, text, dismiss);
    region.append(toast);
}
