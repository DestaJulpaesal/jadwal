<?php

namespace App\Models;

use App\Enums\GoalDirection;
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

    protected $attributes = ['direction' => 'increase'];

    protected $fillable = [
        'category_id', 'title', 'description', 'type', 'target_value', 'start_value', 'unit',
        'due_on', 'status', 'habit_id', 'wallet_id', 'direction',
    ];

    protected $casts = [
        'direction' => GoalDirection::class,
        'type' => GoalType::class,
        'status' => GoalStatus::class,
        'target_value' => 'integer',
        'start_value' => 'integer',
        'due_on' => 'date',
    ];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** @return BelongsTo<Habit, $this> */
    public function habit(): BelongsTo
    {
        return $this->belongsTo(Habit::class);
    }

    /** @return BelongsTo<Wallet, $this> */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    /** @return HasMany<GoalUpdate, $this> */
    public function updates(): HasMany
    {
        return $this->hasMany(GoalUpdate::class);
    }
}
