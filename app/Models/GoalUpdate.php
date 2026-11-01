<?php

namespace App\Models;

use App\Models\Concerns\CastsBusinessDates;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoalUpdate extends Model
{
    use CastsBusinessDates;
    use HasFactory;

    protected array $businessDateColumns = ['recorded_on'];

    protected $fillable = ['goal_id', 'amount', 'recorded_on', 'note'];

    protected $casts = [
        'amount' => 'integer',
        'recorded_on' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function goal(): BelongsTo
    {
        return $this->belongsTo(Goal::class);
    }
}
