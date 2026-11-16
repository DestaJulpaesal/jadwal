@extends('layouts.user')

@section('title', 'Target')

@section('content')
    <div class="mx-auto max-w-5xl space-y-6">
        <x-page-header eyebrow="Langkah demi langkah" title="Target" description="Pantau kemajuan dari hal yang ingin kamu capai.">
            <x-button href="{{ route('user.goals.create') }}" variant="primary" icon="tambah">Buat target</x-button>
        </x-page-header>
        @include('user.partials.habits-subnav')
        <form method="GET" action="{{ route('user.goals.index') }}" class="surface-card p-4" data-auto-submit>
            <x-segmented name="status" label="Status target" :options="$statusOptions" :value="$status" />
            <x-button type="submit" class="sr-only" data-submit-default>{{ __('ui.habit.list_submit') }}</x-button>
        </form>
        <section class="space-y-4">
            @forelse ($goals as $goal)
                <article class="surface-card p-5">
                    <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="font-semibold">{{ $goal->title }}</h2>
                                <x-badge>{{ $goal->direction->label() }}</x-badge>
                            </div>
                            @if($goal->description)<p class="mt-1 text-sm text-muted">{{ $goal->description }}</p>@endif
                            <p class="mt-2 text-sm text-muted">{{ $goal->current_value }} / {{ $goal->target_value }} {{ $goal->unit }}</p>
                            <x-progress-bar class="mt-2 max-w-xl" :value="$goal->progress_percent" :label="'Progres '.$goal->title" />
                        </div>
                        <div class="flex flex-wrap items-end gap-2">
                            @if ($status !== 'archived')
                                <x-button href="{{ route('user.goals.edit', $goal) }}" variant="secondary">Ubah</x-button>
                            @endif
                            @if ($status === 'active' && $goal->type->value === 'number' && $goal->progress_percent < 100)
                                <form method="POST" action="{{ route('user.goals.updates.store', $goal) }}" class="flex flex-wrap items-end gap-2">
                                    @csrf
                                    <div>
                                        <label class="field-label" for="progress-{{ $goal->id }}">{{ __('ui.goal.progress_change') }}</label>
                                        <input id="progress-{{ $goal->id }}" name="amount" type="number"
                                            min="-{{ \App\Support\Rupiah::MAX_AMOUNT }}" max="{{ \App\Support\Rupiah::MAX_AMOUNT }}"
                                            step="1" required class="form-control">
                                        <p class="field-help">{{ __('ui.goal.progress_change_help') }}</p>
                                    </div>
                                    <input type="hidden" name="recorded_on" value="{{ $today }}">
                                    <x-button variant="primary">Catat</x-button>
                                </form>
                            @elseif ($status === 'active' && $goal->progress_percent >= 100)
                                <form method="POST" action="{{ route('user.goals.achieve', $goal) }}">
                                    @csrf
                                    @method('PATCH')
                                    <x-button variant="primary">Tandai tercapai</x-button>
                                </form>
                            @endif
                            @if ($status === 'archived')
                                <form method="POST" action="{{ route('user.goals.restore', $goal) }}">
                                    @csrf
                                    @method('PATCH')
                                    <x-button variant="secondary" icon="kembali">Pulihkan</x-button>
                                </form>
                                <x-confirm-action :action="route('user.goals.destroy', $goal)"
                                    title="Hapus target permanen?"
                                    :message="'Target '.$goal->title.' dan seluruh catatan progresnya akan dihapus permanen.'"
                                    label="Hapus permanen" />
                            @else
                                <x-confirm-action :action="route('user.goals.archive', $goal)" method="PATCH"
                                    title="Arsipkan target?"
                                    :message="'Target '.$goal->title.' tidak lagi muncul di daftar aktif. Riwayat progresnya tetap tersimpan.'"
                                    label="Arsipkan" tone="secondary" icon="nonaktifkan" />
                            @endif
                        </div>
                    </div>
                    @if ($goal->type->value === 'number' && $goal->updates->isNotEmpty())
                        <details class="mt-4 border-t border-line pt-3">
                            <summary class="min-h-11 cursor-pointer font-medium text-primary-800">{{ __('ui.goal.progress_history') }}</summary>
                            <ul class="divide-y divide-line">
                                @foreach ($goal->updates as $update)
                                    <li class="flex flex-wrap items-center justify-between gap-2 py-2 text-sm">
                                        <span>
                                            {{ $update->amount > 0 ? '+' : '' }}{{ number_format($update->amount, 0, ',', '.') }}
                                            {{ $goal->unit }} · {{ $update->recorded_on->translatedFormat('j M Y') }}
                                            @if ($update->note) · {{ $update->note }} @endif
                                        </span>
                                        @if ($loop->last && $status !== 'archived')
                                            <x-confirm-action :action="route('user.goals.updates.destroy', [$goal, $update])"
                                                :title="__('ui.goal.undo_title')"
                                                :message="__('ui.goal.undo_message')"
                                                :label="__('ui.goal.undo_action')" icon="kembali" />
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </details>
                    @endif
                    @if($goal->due_on)<p class="mt-3 text-xs text-muted">{{ __('ui.agenda.target_date') }} {{ $goal->due_on->translatedFormat('j F Y') }}</p>@endif
                    @if($goal->remaining_per_day !== null && $goal->remaining_value > 0)
                        @php
                            $remainingLabel = $goal->type->value === 'savings'
                                ? rupiah($goal->remaining_value)
                                : number_format($goal->remaining_value, 0, ',', '.').' '.$goal->unit;
                            $perDayLabel = $goal->type->value === 'savings'
                                ? rupiah($goal->remaining_per_day)
                                : number_format($goal->remaining_per_day, 0, ',', '.').' '.$goal->unit;
                        @endphp
                        <p class="mt-2 text-sm text-muted">{{ __('ui.goal.remaining_per_day', ['remaining' => $remainingLabel, 'per_day' => $perDayLabel]) }}</p>
                    @endif
                </article>
            @empty
                <x-empty-state
                    class="surface-card"
                    :title="$status === 'active' ? 'Belum ada target aktif.' : 'Belum ada target pada status ini.'"
                    description="Target akan muncul di sini sesuai statusnya."
                    :action-url="route('user.goals.create')"
                    action-label="Buat target pertama"
                >
                    <x-slot:icon><x-icon name="target" /></x-slot:icon>
                </x-empty-state>
            @endforelse
        </section>
    </div>
@endsection
