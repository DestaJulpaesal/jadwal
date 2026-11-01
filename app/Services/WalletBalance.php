<?php

namespace App\Services;

use App\Enums\TransactionType;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class WalletBalance
{
    public function forWallet(Wallet $wallet): int
    {
        $outgoing = (int) $wallet->transactions()
            ->whereIn('type', [TransactionType::Expense->value, TransactionType::Transfer->value])
            ->sum('amount');
        $incoming = (int) $wallet->transactions()
            ->where('type', TransactionType::Income->value)
            ->sum('amount');
        $transfersIn = (int) DB::table('transactions')
            ->where('user_id', $wallet->user_id)
            ->where('to_wallet_id', $wallet->id)
            ->where('type', TransactionType::Transfer->value)
            ->sum('amount');

        return $wallet->opening_balance + $incoming + $transfersIn - $outgoing;
    }

    public function forUser(User $user): Collection
    {
        $rows = DB::table('transactions')
            ->selectRaw(
                'wallet_id, to_wallet_id,
                SUM(CASE WHEN type = ? THEN amount WHEN type IN (?, ?) THEN -amount ELSE 0 END) AS wallet_delta,
                SUM(CASE WHEN type = ? THEN amount ELSE 0 END) AS transfer_in',
                [
                    TransactionType::Income->value,
                    TransactionType::Expense->value,
                    TransactionType::Transfer->value,
                    TransactionType::Transfer->value,
                ]
            )
            ->where('user_id', $user->id)
            ->groupBy('wallet_id', 'to_wallet_id')
            ->get();
        $wallets = $user->wallets()->whereNull('archived_at')->orderBy('name')->get();

        return $wallets->map(function (Wallet $wallet) use ($rows) {
            $wallet->setAttribute('calculated_balance', $wallet->opening_balance
                + (int) $rows->where('wallet_id', $wallet->id)->sum('wallet_delta')
                + (int) $rows->where('to_wallet_id', $wallet->id)->sum('transfer_in'));

            return $wallet;
        });
    }
}
