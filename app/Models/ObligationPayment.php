<?php

namespace App\Models;

use App\Models\Concerns\CastsBusinessDates;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ObligationPayment extends Model
{
    use CastsBusinessDates;
    use HasFactory;

    protected array $businessDateColumns = ['due_on', 'paid_on'];

    protected $fillable = ['obligation_id', 'due_on', 'paid_on', 'amount_paid', 'transaction_id'];

    protected $casts = [
        'due_on' => 'date',
        'paid_on' => 'date',
        'amount_paid' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function obligation(): BelongsTo
    {
        return $this->belongsTo(Obligation::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }
}
