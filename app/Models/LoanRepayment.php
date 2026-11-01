<?php

namespace App\Models;

use App\Models\Concerns\CastsBusinessDates;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanRepayment extends Model
{
    use CastsBusinessDates;
    use HasFactory;

    protected array $businessDateColumns = ['paid_on'];

    protected $fillable = ['loan_id', 'amount', 'paid_on', 'wallet_id', 'transaction_id', 'note'];

    protected $casts = [
        'amount' => 'integer',
        'paid_on' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }
}
