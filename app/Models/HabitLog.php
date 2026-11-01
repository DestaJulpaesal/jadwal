<?php

namespace App\Models;

use App\Enums\HabitLogStatus;
use App\Models\Concerns\CastsBusinessDates;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HabitLog extends Model
{
    use CastsBusinessDates;
    use HasFactory;

    protected array $businessDateColumns = ['log_date'];

    protected $fillable = ['habit_id', 'log_date', 'status', 'value', 'note'];

    protected $casts = [
        'status' => HabitLogStatus::class,
        'log_date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function habit(): BelongsTo
    {
        return $this->belongsTo(Habit::class);
    }
}
