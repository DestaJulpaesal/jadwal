<?php

namespace App\Models;

use App\Enums\TransactionType;
use App\Models\Concerns\CastsBusinessDates;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    use CastsBusinessDates;
    use HasFactory;

    protected array $businessDateColumns = ['occurred_on'];

    protected $fillable = [
        'wallet_id', 'to_wallet_id', 'type', 'amount', 'money_category_id',
        'occurred_on', 'note', 'source_type', 'source_id',
    ];

    protected $casts = [
        'type' => TransactionType::class,
        'amount' => 'integer',
        'occurred_on' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function destinationWallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'to_wallet_id');
    }

    public function moneyCategory(): BelongsTo
    {
        return $this->belongsTo(MoneyCategory::class);
    }
}
