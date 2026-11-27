<?php

namespace App\Models;

use App\Enums\WalletType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read int $calculated_balance
 */
class Wallet extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'type', 'opening_balance', 'archived_at'];

    protected $casts = [
        'type' => WalletType::class,
        'opening_balance' => 'integer',
        'archived_at' => 'datetime',
    ];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<Transaction, $this> */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /** @return HasMany<Transaction, $this> */
    public function destinationTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'to_wallet_id');
    }
}
