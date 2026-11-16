export const initializeTaskBulkActions = () => {
    const bulkForm = document.querySelector('#bulk-task-form');
    const deleteForm = document.querySelector('#bulk-delete-form');
    const selectedCount = document.querySelector('[data-bulk-selected-count]');

    if (!bulkForm || !deleteForm || !selectedCount) {
        return;
    }

    const syncSelection = () => {
        const selectedIds = [...document.querySelectorAll('[data-bulk-task-checkbox]:checked')]
            .map((checkbox) => checkbox.value);

        [bulkForm, deleteForm].forEach((form) => {
            form.querySelectorAll('[data-bulk-task-id]').forEach((input) => input.remove());

            selectedIds.forEach((id) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'task_ids[]';
                input.value = id;
                input.dataset.bulkTaskId = '';
                form.append(input);
            });
        });

        const template = selectedCount.dataset.selectedCountTemplate ?? '';
        selectedCount.textContent = template.replace(':count', String(selectedIds.length));

        const actionButton = document.querySelector('[data-bulk-action-submit]');
        const deleteButton = document.querySelector('[data-bulk-delete-submit] button');
        if (actionButton) {
            actionButton.disabled = selectedIds.length === 0;
        }
        if (deleteButton) {
            deleteButton.disabled = selectedIds.length === 0;
        }
    };

    document.addEventListener('change', (event) => {
        if (event.target instanceof HTMLInputElement && event.target.matches('[data-bulk-task-checkbox]')) {
            syncSelection();
        }
    });

    syncSelection();
};
