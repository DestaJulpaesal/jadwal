<?php

namespace App\Models;

use App\Enums\MoneyCategoryType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MoneyCategory extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'type', 'archived_at'];

    protected $casts = [
        'type' => MoneyCategoryType::class,
        'archived_at' => 'datetime',
    ];

    public function setNameAttribute(string $value): void
    {
        $this->attributes['name'] = $value;
        $this->attributes['name_normalized'] = mb_strtolower($value, 'UTF-8');
    }

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

    /** @return HasMany<Obligation, $this> */
    public function obligations(): HasMany
    {
        return $this->hasMany(Obligation::class);
    }

    /** @return HasMany<Budget, $this> */
    public function budgets(): HasMany
    {
        return $this->hasMany(Budget::class);
    }
}
