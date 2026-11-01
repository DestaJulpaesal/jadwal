<?php

namespace App\Models;

use App\Enums\LoanDirection;
use App\Models\Concerns\CastsBusinessDates;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Loan extends Model
{
    use CastsBusinessDates;
    use HasFactory;

    protected array $businessDateColumns = ['loan_date', 'due_on'];

    protected $fillable = [
        'direction', 'counterparty', 'amount', 'loan_date', 'due_on', 'wallet_id', 'note', 'archived_at',
    ];

    protected $casts = [
        'direction' => LoanDirection::class,
        'amount' => 'integer',
        'loan_date' => 'date',
        'due_on' => 'date',
        'archived_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function repayments(): HasMany
    {
        return $this->hasMany(LoanRepayment::class);
    }
}
