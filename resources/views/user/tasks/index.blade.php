@extends('layouts.user')

@section('title', 'Tugas')

@section('content')
    <div class="mx-auto max-w-6xl">
        <x-page-header class="mb-5" eyebrow="Ruang kerja pribadi" title="Daftar tugas" description="Fokus pada yang harus dikerjakan sekarang.">
            <x-button href="{{ route('user.tasks.create') }}" variant="primary" class="min-h-11 md:inline-flex"><x-concept-icon name="tk" class="h-5 w-5" />Tambah tugas</x-button>
        </x-page-header>

        @if (session('schedule_conflicts'))
            <x-alert type="warning" class="mb-5">
                <p class="font-semibold">Perhatian: jadwal berhasil disimpan, tetapi bertabrakan dengan:</p>
                <ul class="mt-2 list-inside list-disc">
                    @foreach (session('schedule_conflicts') as $conflict)
                        <li>{{ $conflict['title'] }} ({{ $conflict['starts_at'] }}–{{ $conflict['ends_at'] }})</li>
                    @endforeach
                </ul>
            </x-alert>
        @endif

        <nav class="mb-4 overflow-x-auto" aria-label="Filter cepat tugas">
            <div class="flex min-w-max gap-2">
                <x-button href="{{ route('user.tasks.index') }}" variant="{{ ! request()->hasAny(['due', 'status_id', 'search', 'category_id']) ? 'primary' : 'secondary' }}" class="min-h-11">Semua {{ $taskCounts['all'] }}</x-button>
                <x-button href="{{ route('user.tasks.index', ['due' => 'today']) }}" variant="{{ ($filters['due'] ?? '') === 'today' ? 'primary' : 'secondary' }}" class="min-h-11">Hari ini {{ $taskCounts['today'] }}</x-button>
                <x-button href="{{ route('user.tasks.index', ['due' => 'upcoming']) }}" variant="{{ ($filters['due'] ?? '') === 'upcoming' ? 'primary' : 'secondary' }}" class="min-h-11">Akan datang {{ $taskCounts['upcoming'] }}</x-button>
                <x-button href="{{ route('user.tasks.index', ['due' => 'overdue']) }}" variant="{{ ($filters['due'] ?? '') === 'overdue' ? 'primary' : 'secondary' }}" class="min-h-11">Terlambat {{ $taskCounts['overdue'] }}</x-button>
                @if ($completedStatus)
                    <x-button href="{{ route('user.tasks.index', ['status_id' => $completedStatus->id]) }}" variant="{{ (string) ($filters['status_id'] ?? '') === (string) $completedStatus->id ? 'primary' : 'secondary' }}" class="min-h-11">Selesai {{ $taskCounts['completed'] }}</x-button>
                @endif
            </div>
        </nav>

        <details class="surface-card mb-4 {{ request()->hasAny(['search', 'status_id', 'due', 'category_id', 'sort']) ? 'open' : '' }}">
            <summary class="flex min-h-12 cursor-pointer items-center justify-between gap-3 px-4 py-3 font-semibold">
                <span class="inline-flex items-center gap-2"><x-concept-icon name="sc" class="h-5 w-5" />Cari & filter</span>
                <span class="text-sm font-normal text-muted">{{ request()->hasAny(['search', 'status_id', 'due', 'category_id', 'sort']) ? 'Filter aktif' : 'Judul, status, jadwal, kategori' }}</span>
            </summary>
            <form method="GET" action="{{ route('user.tasks.index') }}" class="grid gap-4 border-t border-line p-4 sm:grid-cols-2 lg:grid-cols-3">
                <div>
                    <label for="search" class="field-label">Cari tugas</label>
                    <input id="search" name="search" value="{{ $filters['search'] ?? '' }}" type="search" autocomplete="off" placeholder="Judul atau catatan" class="form-control">
                </div>
                <div>
                    <label for="status_id" class="field-label">Status</label>
                    <select id="status_id" name="status_id" class="form-control">
                        <option value="">Semua status</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status->id }}" @selected((string) ($filters['status_id'] ?? '') === (string) $status->id)>{{ $status->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="due" class="field-label">Waktu tugas</label>
                    <select id="due" name="due" class="form-control">
                        <option value="">Semua jadwal</option>
                        <option value="today" @selected(($filters['due'] ?? '') === 'today')>Hari ini</option>
                        <option value="upcoming" @selected(($filters['due'] ?? '') === 'upcoming')>Akan datang</option>
                        <option value="overdue" @selected(($filters['due'] ?? '') === 'overdue')>{{ __('ui.agenda.overdue') }}</option>
                        <option value="none" @selected(($filters['due'] ?? '') === 'none')>Tanpa tenggat</option>
                    </select>
                </div>
                <div>
                    <label for="category_id" class="field-label">{{ __('ui.task.filters.category') }}</label>
                    <select id="category_id" name="category_id" class="form-control">
                        <option value="">{{ __('ui.task.filters.all_categories') }}</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected((string) ($filters['category_id'] ?? '') === (string) $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="sort" class="field-label">{{ __('ui.task.filters.sort') }}</label>
                    <select id="sort" name="sort" class="form-control">
                        <option value="due" @selected(($filters['sort'] ?? 'due') === 'due')>{{ __('ui.task.filters.sort_due') }}</option>
                        <option value="created" @selected(($filters['sort'] ?? '') === 'created')>{{ __('ui.task.filters.sort_created') }}</option>
                        <option value="title" @selected(($filters['sort'] ?? '') === 'title')>{{ __('ui.task.filters.sort_title') }}</option>
                    </select>
                </div>
                <div class="flex items-end gap-2">
                    <x-button type="submit" variant="primary" class="min-h-11 flex-1">Terapkan</x-button>
                    <x-button href="{{ route('user.tasks.index') }}" variant="secondary" class="min-h-11 flex-1">Reset</x-button>
                </div>
            </form>
        </details>

        <div class="surface-card mb-5 flex flex-wrap items-end gap-3 p-4">
            <form id="bulk-task-form" method="POST" action="{{ route('user.tasks.bulk') }}" class="flex flex-wrap items-end gap-3">
                @csrf
                @method('PATCH')
                <div>
                    <label for="bulk-action" class="field-label">{{ __('ui.task.bulk.action') }}</label>
                    <select id="bulk-action" name="action" class="form-control">
                        <option value="complete">{{ __('ui.task.bulk.complete') }}</option>
                        <option value="status">{{ __('ui.task.bulk.change_status') }}</option>
                    </select>
                </div>
                <div>
                    <label for="bulk-status" class="field-label">{{ __('ui.task.bulk.new_status') }}</label>
                    <select id="bulk-status" name="task_status_id" class="form-control">
                        <option value="">{{ __('ui.task.bulk.choose_status') }}</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status->id }}">{{ $status->name }}</option>
                        @endforeach
                    </select>
                </div>
                <x-button type="submit" variant="secondary" disabled data-bulk-action-submit>{{ __('ui.task.bulk.apply') }}</x-button>
                <span class="text-sm text-muted" aria-live="polite" data-bulk-selected-count
                    data-selected-count-template="{{ __('ui.task.bulk.selected_count', ['count' => ':count']) }}">
                    {{ __('ui.task.bulk.selected_count', ['count' => 0]) }}
                </span>
            </form>
            <x-confirm-action
                :action="route('user.tasks.bulk')"
                method="PATCH"
                form-id="bulk-delete-form"
                :fields="['action' => 'delete']"
                :title="__('ui.task.bulk.delete_title')"
                :message="__('ui.task.bulk.delete_message')"
                :label="__('ui.task.bulk.delete_selected')"
                :disabled="true"
                data-bulk-delete-submit
            />
        </div>

        <section class="surface-card overflow-hidden">
            @if ($tasks->isEmpty())
                <div class="px-5 py-12 text-center sm:px-8">
                    <span class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-primary-50 text-primary-800"><x-concept-icon name="tk" class="h-7 w-7" /></span>
                    <h2 class="text-lg font-semibold">{{ request()->hasAny(['search', 'status_id', 'due', 'category_id']) ? 'Tidak ada tugas yang cocok.' : 'Belum ada tugas.' }}</h2>
                    <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-muted">{{ request()->hasAny(['search', 'status_id', 'due', 'category_id']) ? 'Coba ubah kata pencarian atau filter, atau tampilkan semua tugas.' : 'Tambahkan tugas pertamamu agar rencana mudah diingat dan diatur.' }}</p>
                    <div class="mt-5 flex flex-col justify-center gap-2 sm:flex-row">
                        @if (request()->hasAny(['search', 'status_id', 'due', 'category_id']))
                            <x-button href="{{ route('user.tasks.index') }}" variant="secondary">Tampilkan semua tugas</x-button>
                        @endif
                        <x-button href="{{ route('user.tasks.create') }}" variant="primary">Tambah tugas</x-button>
                    </div>
                </div>
            @else
                <ul class="divide-y divide-line">
                    @foreach ($tasks as $task)
                        @php
                            $startsAt = $task->starts_at?->timezone(auth()->user()->timezone);
                            $endsAt = $task->ends_at?->timezone(auth()->user()->timezone);
                            $dueDate = $task->due_date?->timezone(auth()->user()->timezone);
                        @endphp
                        <li class="flex flex-col gap-3 p-3 sm:p-4 lg:flex-row lg:items-center lg:justify-between">
                            <div class="flex min-w-0 items-start gap-3">
                                <label class="inline-flex min-h-11 min-w-11 cursor-pointer items-center justify-center">
                                    <input type="checkbox" value="{{ $task->id }}" data-bulk-task-checkbox
                                        aria-label="{{ __('ui.task.bulk.select_task', ['title' => $task->title]) }}"
                                        class="h-5 w-5 rounded border-line text-primary-700 focus:ring-primary-700">
                                    <span class="sr-only">{{ __('ui.task.bulk.select_task', ['title' => $task->title]) }}</span>
                                </label>
                                <div class="min-w-0">
                                    <h2 class="break-words font-semibold">{{ $task->title }}</h2>
                                    @if ($task->description)
                                        <p class="mt-1 whitespace-pre-line break-words text-sm leading-6 text-muted">{{ $task->description }}</p>
                                    @endif
                                    <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-muted">
                                    @if ($task->recurrence !== 'none')
                                        <span class="inline-flex items-center gap-1" aria-label="{{ __('ui.task.recurrence.label') }}">
                                            <span aria-hidden="true">↻</span>
                                            {{ __('ui.task.recurrence.label') }}
                                        </span>
                                    @endif
                                    @if ($task->category)
                                        <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full" style="background-color: {{ $task->category->displayColor() }}" aria-hidden="true"></span>{{ $task->category->name }}</span>
                                    @else
                                        <span>{{ __('ui.money.uncategorized') }}</span>
                                    @endif
                                    <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-ink">{{ $task->status->name ?? __('ui.task_status.no_status') }}</span>
                                    <span>
                                        @if ($startsAt)
                                            {{ $startsAt->translatedFormat('j M Y') }} · {{ $startsAt->format('H:i') }}@if ($endsAt)–{{ $endsAt->format('H:i') }}@endif
                                        @elseif ($dueDate)
                                            {{ __('ui.agenda.due') }} {{ $dueDate->translatedFormat('j M Y, H:i') }}
                                        @else
                                            Tanpa tenggat
                                        @endif
                                    </span>
                                </div>
                                </div>
                            </div>
                            <x-task-actions :task="$task" show-delete />
                        </li>
                    @endforeach
                </ul>
            @endif
            <div class="border-t border-line px-4 py-4 sm:px-5">{{ $tasks->links() }}</div>
        </section>
    </div>
@endsection
