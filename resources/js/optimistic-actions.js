export async function handleOptimisticAction(event, showErrorToast) {
    const form = event.target;
    const messages = document.body.dataset;

    if (!navigator.onLine) {
        showErrorToast(messages.offlineMessage);
        return;
    }

    const button = event.submitter instanceof HTMLButtonElement
        ? event.submitter
        : form.querySelector('button[type="submit"]');
    if (!button || button.disabled || button.getAttribute('aria-disabled') === 'true') {
        return;
    }

    const label = button.querySelector('[data-button-label]');
    const originalLabel = label?.textContent.trim() || button.textContent.trim();
    const originalDisabled = button.disabled;
    const originalAriaDisabled = button.getAttribute('aria-disabled');
    const wasFocused = document.activeElement === button;
    const action = form.dataset.optimisticAction;
    const taskRow = action === 'complete-task' ? form.closest('li') : null;
    const taskTitle = taskRow?.querySelector('h2');
    const originalTaskTitleClass = taskTitle?.className;
    const habitStatus = form.closest('.habit-card')?.querySelector('[data-habit-status]');
    const originalHabitStatus = habitStatus?.innerHTML;
    const originalHabitStatusClass = habitStatus?.className;
    const submission = new FormData(form);
    let sessionExpired = false;
    let failureMessage = messages.notSavedMessage;
    if (event.submitter instanceof HTMLButtonElement && event.submitter.name) {
        submission.set(event.submitter.name, event.submitter.value);
    }
    const token = form.querySelector('input[name="_token"]')?.value;

    button.disabled = true;
    button.setAttribute('aria-busy', 'true');
    const optimisticLabel = action === 'complete-task' || action === 'habit-checkin'
        ? messages.actionSavedLabel
        : messages.actionCancelledLabel;
    if (label) {
        label.textContent = optimisticLabel;
    } else {
        button.textContent = optimisticLabel;
    }
    taskTitle?.classList.add('line-through');
    if (habitStatus) {
        const requestedStatus = submission.get('status');
        habitStatus.textContent = action === 'cancel-habit-checkin'
            ? messages.habitNotRecordedLabel
            : requestedStatus === 'skipped' ? messages.habitSkippedLabel : messages.habitDoneLabel;
    }

    try {
        const response = await fetch(form.action, {
            method: 'POST',
            body: submission,
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                ...(token ? { 'X-CSRF-TOKEN': token } : {}),
            },
        });

        if (!response.ok) {
            const error = await response.json().catch(() => null);
            if (response.status === 401 || response.status === 419) {
                sessionExpired = true;
                failureMessage = messages.sessionExpiredMessage;
                throw new Error(failureMessage);
            }
            if (response.status === 422) {
                const validationMessage = Object.values(error?.errors || {}).flat().find((message) => typeof message === 'string');
                failureMessage = validationMessage || messages.validationMessage;
                throw new Error(failureMessage);
            }

            failureMessage = typeof error?.message === 'string' ? error.message : messages.notSavedMessage;
            throw new Error(failureMessage);
        }

        const result = await response.json();
        const resultLabel = result.status === 'cancelled'
            ? messages.actionCancelledLabel
            : result.status === 'skipped' ? messages.actionSkippedLabel : messages.actionSavedLabel;
        if (label) {
            label.textContent = resultLabel;
        } else {
            button.textContent = resultLabel;
        }
        button.removeAttribute('aria-busy');
        button.disabled = false;
        button.setAttribute('aria-disabled', 'true');
        button.setAttribute('aria-live', 'polite');
        if (wasFocused) {
            button.focus({ preventScroll: true });
        }
        if (habitStatus) {
            habitStatus.textContent = result.status === 'cancelled'
                ? messages.habitNotRecordedLabel
                : result.status === 'skipped' ? messages.habitSkippedLabel : messages.habitDoneLabel;
            habitStatus.classList.remove('habit-status-pending', 'habit-status-done', 'habit-status-skipped');
            habitStatus.classList.add(result.status === 'cancelled' ? 'habit-status-pending' : `habit-status-${result.status}`);
        }
        updateDashboardHabitProgress(action, result.status);
    } catch {
        button.disabled = originalDisabled;
        button.removeAttribute('aria-busy');
        if (originalAriaDisabled === null) {
            button.removeAttribute('aria-disabled');
        } else {
            button.setAttribute('aria-disabled', originalAriaDisabled);
        }
        if (label) {
            label.textContent = originalLabel;
        } else {
            button.textContent = originalLabel;
        }
        if (taskTitle) {
            taskTitle.className = originalTaskTitleClass;
        }
        if (habitStatus) {
            habitStatus.innerHTML = originalHabitStatus;
            habitStatus.className = originalHabitStatusClass;
        }
        if (wasFocused && !button.disabled) {
            button.focus({ preventScroll: true });
        }
        if (sessionExpired) {
            showErrorToast(messages.sessionExpiredMessage, {
                href: messages.loginUrl,
                label: messages.loginAgainLabel,
            });
        } else {
            showErrorToast(failureMessage);
        }
    }
}

function updateDashboardHabitProgress(action, status) {
    if (action !== 'habit-checkin' && action !== 'cancel-habit-checkin') {
        return;
    }

    const progress = document.querySelector('[data-habit-completion-progress]');
    const count = progress?.querySelector('[data-habit-completion-count]');
    if (!progress || !count) {
        return;
    }

    const previouslyCompleted = action === 'cancel-habit-checkin';
    const completed = status === 'done';
    const total = Number(progress.dataset.total);
    const current = Number(progress.dataset.completed);
    const next = Math.max(0, Math.min(total, current + Number(completed) - Number(previouslyCompleted)));
    progress.dataset.completed = String(next);
    count.textContent = String(next);
}
