<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
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
        $pattern = "%{$query}%";

        $sections = collect([
            [
                'title' => 'Tugas',
                'icon' => 'tk',
                'records' => $user->tasks()
                    ->where(fn ($builder) => $builder->where('title', 'like', $pattern)->orWhere('description', 'like', $pattern))
                    ->latest()
                    ->limit(8)
                    ->get()
                    ->map(fn ($task) => [
                        'title' => $task->title,
                        'description' => $task->description ?: 'Buka daftar tugas untuk melihat detail.',
                        'url' => route('user.tasks.index'),
                    ]),
            ],
            [
                'title' => 'Kebiasaan',
                'icon' => 'fl',
                'records' => $user->habits()
                    ->where(fn ($builder) => $builder->where('title', 'like', $pattern)->orWhere('description', 'like', $pattern))
                    ->latest()
                    ->limit(8)
                    ->get()
                    ->map(fn ($habit) => [
                        'title' => $habit->title,
                        'description' => $habit->description ?: 'Buka daftar kebiasaan untuk melihat detail.',
                        'url' => route('user.habits.index'),
                    ]),
            ],
            [
                'title' => 'Transaksi',
                'icon' => 'wl',
                'records' => $user->transactions()
                    ->with(['wallet:id,name', 'moneyCategory:id,name'])
                    ->where(function ($builder) use ($pattern, $user) {
                        $builder->where('note', 'like', $pattern)
                            ->orWhereHas('wallet', fn ($wallet) => $wallet->where('user_id', $user->id)->where('name', 'like', $pattern))
                            ->orWhereHas('moneyCategory', fn ($category) => $category->where('user_id', $user->id)->where('name', 'like', $pattern));
                    })
                    ->latest('occurred_on')
                    ->limit(8)
                    ->get()
                    ->map(fn ($transaction) => [
                        'title' => $transaction->note ?: $transaction->moneyCategory?->name ?: $transaction->wallet?->name ?: 'Transaksi',
                        'description' => trim(implode(' · ', array_filter([
                            $transaction->type->label(),
                            $transaction->occurred_on->format('d M Y'),
                            $transaction->moneyCategory?->name,
                        ]))),
                        'url' => route('user.money.index'),
                    ]),
            ],
            [
                'title' => 'Tagihan dan cicilan',
                'icon' => 'bl',
                'records' => $user->obligations()
                    ->where(fn ($builder) => $builder->where('title', 'like', $pattern)->orWhere('note', 'like', $pattern))
                    ->latest()
                    ->limit(8)
                    ->get()
                    ->map(fn ($obligation) => [
                        'title' => $obligation->title,
                        'description' => $obligation->note ?: $obligation->kind->label(),
                        'url' => route('user.obligations.index'),
                    ]),
            ],
            [
                'title' => 'Utang dan piutang',
                'icon' => 'us',
                'records' => $user->loans()
                    ->where(fn ($builder) => $builder->where('counterparty', 'like', $pattern)->orWhere('note', 'like', $pattern))
                    ->latest()
                    ->limit(8)
                    ->get()
                    ->map(fn ($loan) => [
                        'title' => $loan->counterparty,
                        'description' => $loan->note ?: $loan->direction->label(),
                        'url' => route('user.loans.index'),
                    ]),
            ],
            [
                'title' => 'Target',
                'icon' => 'bk',
                'records' => $user->goals()
                    ->where(fn ($builder) => $builder->where('title', 'like', $pattern)->orWhere('description', 'like', $pattern))
                    ->latest()
                    ->limit(8)
                    ->get()
                    ->map(fn ($goal) => [
                        'title' => $goal->title,
                        'description' => $goal->description ?: $goal->status->label(),
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
