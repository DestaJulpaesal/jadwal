<?php

namespace App\Services;

use App\Models\Loan;

class LoanBalance
{
    public function paid(Loan $loan): int
    {
        return $loan->relationLoaded('repayments')
            ? (int) $loan->repayments->sum('amount')
            : (int) $loan->repayments()->sum('amount');
    }

    public function remaining(Loan $loan): int
    {
        return max(0, $loan->amount - $this->paid($loan));
    }
}
