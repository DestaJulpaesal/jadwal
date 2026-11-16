<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Support\Like;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SearchController extends Controller
{
    public function __invoke(Request $request)
    {
        $rawQuery = $request->query('q');
        $validator = Validator::make([
            'q' => is_string($rawQuery) ? trim($rawQuery) : $rawQuery,
        ], [
            'q' => ['required', 'string', 'max:100'],
        ], __('validation.catalog.search'));

        if ($validator->fails()) {
            return view('user.search', [
                'query' => is_string($request->query('q')) ? $request->query('q') : '',
                'sections' => collect(),
                'searchErrors' => $validator->errors(),
            ]);
        }

        $query = $validator->validated()['q'];
        $user = $request->user();
        $pattern = '%'.Like::escape($query).'%';

        $sections = collect([
            [
                'title' => __('ui.search.tasks'),
                'icon' => 'tk',
                'records' => $user->tasks()
                    ->where(fn ($builder) => $builder->whereRaw("title LIKE ? ESCAPE '!'", [$pattern])
                        ->orWhereRaw("description LIKE ? ESCAPE '!'", [$pattern]))
                    ->latest()
                    ->limit(8)
                    ->get()
                    ->map(fn ($task) => [
                        'title' => $task->title,
                        'description' => $task->description ?: __('ui.search.task_details'),
                        'url' => route('user.tasks.edit', $task),
                    ]),
            ],
            [
                'title' => __('ui.search.habits'),
                'icon' => 'fl',
                'records' => $user->habits()
                    ->where(fn ($builder) => $builder->whereRaw("title LIKE ? ESCAPE '!'", [$pattern])
                        ->orWhereRaw("description LIKE ? ESCAPE '!'", [$pattern]))
                    ->latest()
                    ->limit(8)
                    ->get()
                    ->map(fn ($habit) => [
                        'title' => $habit->title,
                        'description' => trim(implode(' · ', array_filter([
                            $habit->archived_at ? __('ui.entity_status.archived_label') : null,
                            $habit->description ?: __('ui.search.habit_details'),
                        ]))),
                        'url' => route('user.habits.index'),
                    ]),
            ],
            [
                'title' => __('ui.search.transactions'),
                'icon' => 'wl',
                'records' => $user->transactions()
                    ->with(['wallet:id,name,archived_at', 'destinationWallet:id,name,archived_at', 'moneyCategory:id,name,archived_at'])
                    ->where(function ($builder) use ($pattern, $user) {
                        $builder->whereRaw("note LIKE ? ESCAPE '!'", [$pattern])
                            ->orWhereHas('wallet', fn ($wallet) => $wallet->where('user_id', $user->id)
                                ->whereRaw("name LIKE ? ESCAPE '!'", [$pattern]))
                            ->orWhereHas('moneyCategory', fn ($category) => $category->where('user_id', $user->id)
                                ->whereRaw("name LIKE ? ESCAPE '!'", [$pattern]));
                    })
                    ->latest('occurred_on')
                    ->limit(8)
                    ->get()
                    ->map(fn ($transaction) => [
                        'title' => $transaction->note ?: $transaction->moneyCategory?->name ?: $transaction->wallet?->name ?: __('ui.search.transactions'),
                        'description' => trim(implode(' · ', array_filter([
                            ($transaction->wallet?->archived_at
                                || $transaction->destinationWallet?->archived_at
                                || $transaction->moneyCategory?->archived_at) ? __('ui.entity_status.archived_label') : null,
                            $transaction->type->label(),
                            $transaction->occurred_on->format('d M Y'),
                            $transaction->moneyCategory?->name,
                        ]))),
                        'url' => route('user.money.index'),
                    ]),
            ],
            [
                'title' => __('ui.search.obligations'),
                'icon' => 'bl',
                'records' => $user->obligations()
                    ->where(fn ($builder) => $builder->whereRaw("title LIKE ? ESCAPE '!'", [$pattern])
                        ->orWhereRaw("note LIKE ? ESCAPE '!'", [$pattern]))
                    ->latest()
                    ->limit(8)
                    ->get()
                    ->map(fn ($obligation) => [
                        'title' => $obligation->title,
                        'description' => trim(implode(' · ', array_filter([
                            $obligation->archived_at ? __('ui.entity_status.archived_label') : null,
                            $obligation->note ?: $obligation->kind->label(),
                        ]))),
                        'url' => route('user.obligations.index'),
                    ]),
            ],
            [
                'title' => __('ui.search.loans'),
                'icon' => 'us',
                'records' => $user->loans()
                    ->where(fn ($builder) => $builder->whereRaw("counterparty LIKE ? ESCAPE '!'", [$pattern])
                        ->orWhereRaw("note LIKE ? ESCAPE '!'", [$pattern]))
                    ->latest()
                    ->limit(8)
                    ->get()
                    ->map(fn ($loan) => [
                        'title' => $loan->counterparty,
                        'description' => trim(implode(' · ', array_filter([
                            $loan->archived_at ? __('ui.entity_status.archived_label') : null,
                            $loan->note ?: $loan->direction->label(),
                        ]))),
                        'url' => route('user.loans.index'),
                    ]),
            ],
            [
                'title' => __('ui.search.goals'),
                'icon' => 'bk',
                'records' => $user->goals()
                    ->where(fn ($builder) => $builder->whereRaw("title LIKE ? ESCAPE '!'", [$pattern])
                        ->orWhereRaw("description LIKE ? ESCAPE '!'", [$pattern]))
                    ->latest()
                    ->limit(8)
                    ->get()
                    ->map(fn ($goal) => [
                        'title' => $goal->title,
                        'description' => trim(implode(' · ', array_filter([
                            $goal->status->value === 'archived' ? __('ui.entity_status.archived_label') : null,
                            $goal->description ?: $goal->status->label(),
                        ]))),
                        'url' => route('user.goals.index'),
                    ]),
            ],
        ]);

        return view('user.search', [
            'query' => $query,
            'sections' => $sections,
            'searchErrors' => $validator->errors(),
        ]);
    }
}
