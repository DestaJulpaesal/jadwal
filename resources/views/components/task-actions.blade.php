@props(['task', 'showDelete' => false])

@php
    $isComplete = $task->status?->name === 'Selesai';
@endphp

<div class="flex items-center gap-2">
    @if (! $isComplete)
        <form method="POST" action="{{ route('user.tasks.complete', $task) }}" data-optimistic-action="complete-task">
            @csrf
            @method('PATCH')
            <x-button type="submit" variant="primary" class="min-h-11 min-w-11 px-3 sm:min-w-0" aria-label="Tandai {{ $task->title }} selesai">
                <x-concept-icon name="tk" class="h-5 w-5" />
                <span class="hidden sm:inline">Tandai selesai</span>
            </x-button>
        </form>
    @else
        <span class="inline-flex min-h-11 items-center gap-2 text-sm font-medium text-success">
            <x-concept-icon name="spark" class="h-5 w-5" />
            Selesai
        </span>
    @endif

    <div class="hidden items-center gap-2 sm:flex">
        <x-button href="{{ route('user.tasks.edit', $task) }}" variant="secondary" class="min-h-11 px-3">
            Ubah
        </x-button>
        @if ($showDelete)
            <x-confirm-action
                :action="route('user.tasks.destroy', $task)"
                method="DELETE"
                title="Hapus tugas ini?"
                :message="$task->recurrence !== 'none' ? __('ui.task.recurrence.delete_single_message') : 'Tugas ini akan dihapus permanen dan tidak dapat dipulihkan.'"
                :label="$task->recurrence !== 'none' ? __('ui.task.recurrence.delete_single') : 'Hapus'"
            />
            @if ($task->recurrence !== 'none')
                <x-confirm-action
                    :action="route('user.tasks.destroy-series', $task)"
                    method="DELETE"
                    :title="__('ui.task.recurrence.stop_title')"
                    :message="__('ui.task.recurrence.stop_message')"
                    :label="__('ui.task.recurrence.stop_action')"
                />
            @endif
        @endif
    </div>

    <details class="task-actions relative sm:hidden">
        <summary class="button button--ghost button--icon-only" aria-label="Menu aksi untuk {{ $task->title }}" title="Menu aksi untuk {{ $task->title }}">
            <x-concept-icon name="dt" class="h-5 w-5" />
        </summary>
        <div class="task-actions-menu">
            <a href="{{ route('user.tasks.edit', $task) }}">
                Ubah
            </a>
            @if ($showDelete)
                <x-confirm-action
                    :action="route('user.tasks.destroy', $task)"
                    method="DELETE"
                    title="Hapus tugas ini?"
                    :message="$task->recurrence !== 'none' ? __('ui.task.recurrence.delete_single_message') : 'Tugas ini akan dihapus permanen dan tidak dapat dipulihkan.'"
                    :label="$task->recurrence !== 'none' ? __('ui.task.recurrence.delete_single') : 'Hapus'"
                />
                @if ($task->recurrence !== 'none')
                    <x-confirm-action
                        :action="route('user.tasks.destroy-series', $task)"
                        method="DELETE"
                        :title="__('ui.task.recurrence.stop_title')"
                        :message="__('ui.task.recurrence.stop_message')"
                        :label="__('ui.task.recurrence.stop_action')"
                    />
                @endif
            @endif
        </div>
    </details>
</div>
