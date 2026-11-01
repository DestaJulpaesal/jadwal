<?php

namespace App\Models;

use App\Enums\ObligationKind;
use App\Models\Concerns\CastsBusinessDates;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Obligation extends Model
{
    use CastsBusinessDates;
    use HasFactory;

    protected array $businessDateColumns = ['starts_on'];

    protected $fillable = [
        'kind', 'title', 'amount', 'wallet_id', 'money_category_id', 'due_day', 'starts_on',
        'installments_total', 'remind_days_before', 'note', 'archived_at',
    ];

    protected $casts = [
        'kind' => ObligationKind::class,
        'amount' => 'integer',
        'starts_on' => 'date',
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

    public function moneyCategory(): BelongsTo
    {
        return $this->belongsTo(MoneyCategory::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(ObligationPayment::class);
    }
}
