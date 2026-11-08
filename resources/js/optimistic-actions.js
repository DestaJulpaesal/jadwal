export async function handleOptimisticAction(event, showErrorToast) {
    const form = event.target;

    if (!navigator.onLine) {
        showErrorToast('Kamu sedang offline. Hubungkan kembali ke internet, lalu kirim ulang perubahanmu.');
        return;
    }

    const button = event.submitter instanceof HTMLButtonElement
        ? event.submitter
        : form.querySelector('button[type="submit"]');
    if (!button || button.disabled) {
        return;
    }

    const label = button.querySelector('[data-button-label]');
    const originalLabel = label?.textContent.trim() || button.textContent.trim();
    const originalDisabled = button.disabled;
    const action = form.dataset.optimisticAction;
    const taskRow = action === 'complete-task' ? form.closest('li') : null;
    const taskTitle = taskRow?.querySelector('h2');
    const originalTaskTitleClass = taskTitle?.className;
    const habitStatus = form.closest('.habit-card')?.querySelector('[data-habit-status]');
    const originalHabitStatus = habitStatus?.innerHTML;
    const originalHabitStatusClass = habitStatus?.className;
    const submission = new FormData(form);
    if (event.submitter instanceof HTMLButtonElement && event.submitter.name) {
        submission.set(event.submitter.name, event.submitter.value);
    }
    const token = form.querySelector('input[name="_token"]')?.value;

    button.disabled = true;
    button.setAttribute('aria-busy', 'true');
    if (label) {
        label.textContent = action === 'complete-task' || action === 'habit-checkin'
            ? 'Tersimpan'
            : 'Dibatalkan';
    } else {
        button.textContent = action === 'complete-task' || action === 'habit-checkin'
            ? 'Tersimpan'
            : 'Dibatalkan';
    }
    taskTitle?.classList.add('line-through');
    if (habitStatus) {
        const requestedStatus = submission.get('status');
        habitStatus.textContent = action === 'cancel-habit-checkin'
            ? 'Belum dicatat'
            : requestedStatus === 'skipped' ? 'Libur hari ini' : 'Selesai hari ini';
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
            let message = '';
            try {
                const error = await response.json();
                message = error.message || Object.values(error.errors || {}).flat()[0] || '';
            } catch {
                message = '';
            }
            throw new Error(message || 'Belum tersimpan. Periksa koneksi, lalu coba lagi.');
        }

        const result = await response.json();
        const resultLabel = result.status === 'cancelled'
            ? 'Dibatalkan'
            : result.status === 'skipped' ? 'Dilewati' : 'Selesai';
        if (label) {
            label.textContent = resultLabel;
        } else {
            button.textContent = resultLabel;
        }
        button.removeAttribute('aria-busy');
        if (habitStatus) {
            habitStatus.textContent = result.status === 'cancelled'
                ? 'Belum dicatat'
                : result.status === 'skipped' ? 'Libur hari ini' : 'Selesai hari ini';
            habitStatus.classList.remove('habit-status-pending', 'habit-status-done', 'habit-status-skipped');
            habitStatus.classList.add(result.status === 'cancelled' ? 'habit-status-pending' : `habit-status-${result.status}`);
        }
    } catch (error) {
        button.disabled = originalDisabled;
        button.removeAttribute('aria-busy');
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
        showErrorToast(error instanceof Error ? error.message : 'Belum tersimpan. Periksa koneksi, lalu coba lagi.');
    }
}
