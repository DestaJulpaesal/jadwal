<?php

namespace App\Http\Controllers\User;

use App\Enums\MoneyCategoryType;
use App\Enums\TransactionType;
use App\Enums\WalletType;
use App\Http\Controllers\Controller;
use App\Models\MoneyCategory;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Services\MonthlySummary;
use App\Services\WalletBalance;
use App\Support\Rupiah;
use App\Support\UserClock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class MoneyController extends Controller
{
    public function index(Request $request, WalletBalance $balances, MonthlySummary $summary, UserClock $clock)
    {
        $this->authorize('viewAny', Transaction::class);
        $user = $request->user();
        foreach (config('presets.money_categories') as $type => $names) {
            foreach ($names as $name) {
                $user->moneyCategories()->firstOrCreate(['type' => $type, 'name' => $name]);
            }
        }
        $today = $clock->date($user);
        $month = $request->validate(['month' => ['nullable', 'date_format:Y-m']])['month'] ?? substr($today, 0, 7);
        $wallets = $balances->forUser($user);
        $transactions = $user->transactions()->with(['wallet', 'destinationWallet', 'moneyCategory'])
            ->whereBetween('occurred_on', [
                \Illuminate\Support\Carbon::parse($month)->startOfMonth()->toDateString(),
                \Illuminate\Support\Carbon::parse($month)->endOfMonth()->toDateString(),
            ])->orderByDesc('occurred_on')->orderByDesc('id')->paginate(15)->withQueryString();
        $categories = $user->moneyCategories()->whereNull('archived_at')->orderBy('type')->orderBy('name')->get();
        $totals = $summary->forMonth($user, $month);
        $walletTypes = WalletType::cases();

        return view('user.money.index', compact('wallets', 'transactions', 'categories', 'totals', 'month', 'today', 'walletTypes'));
    }

    public function storeWallet(Request $request)
    {
        $this->authorize('create', Wallet::class);
        $request->merge(['opening_balance' => Rupiah::normalize($request->input('opening_balance'))]);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:60'],
            'type' => ['required', Rule::in(array_column(WalletType::cases(), 'value'))],
            'opening_balance' => ['required', 'integer', 'between:-'.Rupiah::MAX_AMOUNT.','.Rupiah::MAX_AMOUNT],
        ]);
        $request->user()->wallets()->create($data);

        return back()->with('success', 'Dompet berhasil ditambahkan.');
    }

    public function archiveWallet(Request $request, Wallet $wallet)
    {
        $this->authorize('update', $wallet);
        abort_if($wallet->user_id !== $request->user()->id, 404);
        $wallet->update(['archived_at' => now()]);

        return back()->with('success', 'Dompet diarsipkan.');
    }

    public function storeCategory(Request $request)
    {
        $this->authorize('create', MoneyCategory::class);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:60'],
            'type' => ['required', Rule::in(array_column(MoneyCategoryType::cases(), 'value'))],
        ]);
        $request->user()->moneyCategories()->firstOrCreate($data);

        return back()->with('success', 'Kategori berhasil disimpan.');
    }

    public function storeTransaction(Request $request)
    {
        $this->authorize('create', Transaction::class);
        $request->merge(['amount' => Rupiah::normalize($request->input('amount'))]);
        $data = $request->validate([
            'type' => ['required', Rule::in(array_column(TransactionType::cases(), 'value'))],
            'amount' => ['required', 'integer', 'between:1,'.Rupiah::MAX_AMOUNT],
            'wallet_id' => ['required', 'integer', Rule::exists('wallets', 'id')->where('user_id', $request->user()->id)->whereNull('archived_at')],
            'to_wallet_id' => ['nullable', 'integer', Rule::exists('wallets', 'id')->where('user_id', $request->user()->id)->whereNull('archived_at')],
            'money_category_id' => ['nullable', 'integer', Rule::exists('money_categories', 'id')->where('user_id', $request->user()->id)->whereNull('archived_at')],
            'occurred_on' => ['required', 'date_format:Y-m-d'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);
        if ($data['type'] === TransactionType::Transfer->value) {
            $request->validate(['to_wallet_id' => ['required', 'different:wallet_id']]);
            $data['money_category_id'] = null;
        } else {
            $data['to_wallet_id'] = null;
        }
        if (! empty($data['money_category_id'])) {
            $category = $request->user()->moneyCategories()->findOrFail($data['money_category_id']);
            abort_unless($category->type->value === $data['type'], 422, 'Jenis kategori tidak sesuai dengan transaksi.');
        }

        DB::transaction(fn () => $request->user()->transactions()->create($data));

        return back()->with('success', 'Transaksi berhasil dicatat.');
    }
}
