<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Budget extends Model
{
    use HasFactory;

    protected $fillable = ['money_category_id', 'month', 'amount'];

    protected $casts = [
        'amount' => 'integer',
    ];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<MoneyCategory, $this> */
    public function moneyCategory(): BelongsTo
    {
        return $this->belongsTo(MoneyCategory::class);
    }
}
