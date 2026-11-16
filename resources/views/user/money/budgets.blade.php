@php
    $budgetStatuses = [
        'ok' => ['tone' => 'success', 'color' => 'bg-success'],
        'warning' => ['tone' => 'warning', 'color' => 'bg-warning'],
        'over' => ['tone' => 'danger', 'color' => 'bg-danger'],
        'unbudgeted' => ['tone' => 'neutral', 'color' => 'bg-primary-700'],
    ];
    $onReportsPage = request()->routeIs('user.reports.index');
@endphp

<section class="concept-card concept-card-white" aria-labelledby="budget-title">
    <div class="concept-card-header">
        <div>
            <h2 id="budget-title" class="text-lg font-semibold">{{ __('ui.budget.title') }}</h2>
            <p class="text-sm">{{ __('ui.budget.description') }}</p>
        </div>
        @unless ($onReportsPage)
            <form method="GET" action="{{ route('user.money.index') }}" class="flex items-end gap-2" data-auto-submit>
                <input type="hidden" name="section" value="overview">
                <div>
                    <label class="sr-only" for="budget-month">{{ __('ui.budget.month') }}</label>
                    <input id="budget-month" name="month" type="month" value="{{ $month }}" class="form-control">
                </div>
                <x-button type="submit" variant="secondary" data-submit-default>Terapkan</x-button>
            </form>
        @endunless
    </div>
    <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-muted">{{ $monthLabel }}</p>
        <form method="POST" action="{{ route('user.budgets.copy-previous') }}">
            @csrf
            <input type="hidden" name="month" value="{{ $month }}">
            <x-button type="submit" variant="secondary" icon="kembali">{{ __('ui.budget.copy_previous') }}</x-button>
        </form>
    </div>
    <ul class="concept-list mt-3">
        @forelse ($budgetProgress as $item)
            @php
                $category = $item['category'];
                $status = $item['status'];
                $statusStyle = $budgetStatuses[$status];
                $sheetName = 'budget-'.$category->id;
                $formContext = 'budget-'.$category->id;
                $categoryIsActive = $budgetCategories->contains(fn ($candidate) => $candidate->id === $category->id);
            @endphp
            <li class="concept-item flex-wrap">
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <h3 class="font-medium break-words">{{ $category->name }}</h3>
                        <x-badge :tone="$statusStyle['tone']">{{ __('ui.budget.status_'.$status) }}</x-badge>
                    </div>
                    @if ($item['budget'] !== null)
                        <x-progress-bar
                            class="mt-3"
                            :value="$item['percent']"
                            :label="'Progres anggaran '.$category->name"
                            :description="__('ui.budget.status_'.$status)"
                            :color="$statusStyle['color']" />
                        <p class="mt-1 text-sm text-muted" data-private>{{ __('ui.budget.spent_of_budget', ['spent' => rupiah($item['spent']), 'budget' => rupiah($item['budget'])]) }}</p>
                    @else
                        <p class="mt-2 text-sm text-muted" data-private>{{ __('ui.budget.spent_unbudgeted', ['spent' => rupiah($item['spent'])]) }}</p>
                    @endif
                </div>
                @if ($item['budget_id'] !== null)
                    @if ($categoryIsActive)
                        <x-button type="button" size="sm" variant="secondary" icon="ubah" x-data x-on:click="$dispatch('open-modal', '{{ $sheetName }}')">
                            {{ __('ui.budget.edit') }}
                        </x-button>
                    @endif
                    <x-confirm-action
                        :action="route('user.budgets.destroy', $item['budget_id'])"
                        :title="__('ui.budget.delete_title')"
                        :message="__('ui.budget.delete_message')"
                        :label="__('ui.budget.delete')" />
                @elseif ($categoryIsActive)
                    <x-button type="button" size="sm" variant="primary" icon="tambah" x-data x-on:click="$dispatch('open-modal', '{{ $sheetName }}')">
                        {{ __('ui.budget.set') }}
                    </x-button>
                @endif

                @if ($categoryIsActive)
                    <x-bottom-sheet
                        :name="$sheetName"
                        :title="$item['budget_id'] === null ? __('ui.budget.set') : __('ui.budget.edit')"
                        :show="old('form_context') === $formContext && $errors->any()">
                        @if (old('form_context') === $formContext)
                            <x-error-summary :errors="$errors->messages()" title="Anggaran belum tersimpan. Periksa isian berikut:" />
                        @endif
                        <form
                            method="POST"
                            action="{{ $item['budget_id'] === null ? route('user.budgets.store') : route('user.budgets.update', $item['budget_id']) }}"
                            class="space-y-4"
                            data-dirty-guard>
                            @csrf
                            @if ($item['budget_id'] !== null)
                                @method('PATCH')
                            @endif
                            <input type="hidden" name="form_context" value="{{ $formContext }}">
                            <input type="hidden" name="money_category_id" value="{{ $category->id }}">
                            <input type="hidden" name="month" value="{{ $month }}">
                            <x-field id="budget-amount-{{ $category->id }}" :label="__('ui.budget.amount')" help="Masukkan nominal Rupiah tanpa tanda mata uang." required :error="$errors->first('amount')">
                                <input
                                    id="budget-amount-{{ $category->id }}"
                                    name="amount"
                                    type="text"
                                    inputmode="numeric"
                                    maxlength="15"
                                    value="{{ old('form_context') === $formContext ? old('amount', $item['budget']) : $item['budget'] }}"
                                    required
                                    class="form-control">
                            </x-field>
                            <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                                <x-button type="button" variant="secondary" icon="kembali" x-on:click="$dispatch('close')">Batal</x-button>
                                <x-button type="submit" variant="primary" loading-text="Menyimpan anggaran…">Simpan anggaran</x-button>
                            </div>
                        </form>
                    </x-bottom-sheet>
                @endif
            </li>
        @empty
            <li class="py-4 text-sm text-muted">{{ __('ui.budget.empty') }}</li>
        @endforelse
    </ul>
</section>
