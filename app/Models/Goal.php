<?php

namespace App\Models;

use App\Enums\GoalStatus;
use App\Enums\GoalType;
use App\Models\Concerns\CastsBusinessDates;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Goal extends Model
{
    use CastsBusinessDates;
    use HasFactory;

    protected array $businessDateColumns = ['due_on'];

    protected $fillable = [
        'category_id', 'title', 'description', 'type', 'target_value', 'start_value', 'unit',
        'due_on', 'status', 'habit_id', 'wallet_id',
    ];

    protected $casts = [
        'type' => GoalType::class,
        'status' => GoalStatus::class,
        'target_value' => 'integer',
        'start_value' => 'integer',
        'due_on' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function habit(): BelongsTo
    {
        return $this->belongsTo(Habit::class);
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function updates(): HasMany
    {
        return $this->hasMany(GoalUpdate::class);
    }
}
