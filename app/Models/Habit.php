<?php

namespace App\Models;

use App\Enums\HabitFrequency;
use App\Models\Concerns\CastsBusinessDates;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read HabitLog|null $today_log Computed by dashboard and habit-list queries.
 */
class Habit extends Model
{
    use CastsBusinessDates;
    use HasFactory;

    protected array $businessDateColumns = ['starts_on', 'ends_on'];

    protected $fillable = [
        'category_id', 'title', 'description', 'frequency', 'weekdays', 'weekly_target',
        'goal_value', 'goal_unit', 'preferred_time', 'schedule_times', 'starts_on', 'ends_on', 'archived_at',
    ];

    protected $casts = [
        'frequency' => HabitFrequency::class,
        'weekdays' => 'array',
        'schedule_times' => 'array',
        'starts_on' => 'date',
        'ends_on' => 'date',
        'archived_at' => 'datetime',
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

    /** @return HasMany<HabitLog, $this> */
    public function logs(): HasMany
    {
        return $this->hasMany(HabitLog::class);
    }
}
