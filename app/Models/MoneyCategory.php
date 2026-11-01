<?php

namespace App\Models;

use App\Enums\MoneyCategoryType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MoneyCategory extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'type', 'archived_at'];

    protected $casts = [
        'type' => MoneyCategoryType::class,
        'archived_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
