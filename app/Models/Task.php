<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read TaskStatus|null $status
 * @property-read int|null $all_count
 * @property-read int|null $today_count
 * @property-read int|null $upcoming_count
 * @property-read int|null $overdue_count
 * @property-read int|null $completed_count
 */
class Task extends Model
{
    use HasFactory;

    protected $casts = [
        'due_date' => 'datetime',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'completed_at' => 'datetime',
        'recurrence_days' => 'array',
        'recurrence_until' => 'date',
        'recurrence_anchor_day' => 'integer',
        'occurrence_date' => 'date',
    ];

    protected $fillable = [
        'category_id',
        'task_status_id',
        'title',
        'description',
        'due_date',
        'starts_at',
        'ends_at',
        'completed_at',
        'recurrence',
        'recurrence_days',
        'recurrence_until',
        'recurrence_anchor_day',
        'recurrence_parent_id',
        'occurrence_date',
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

    /** @return BelongsTo<TaskStatus, $this> */
    public function status(): BelongsTo
    {
        return $this->belongsTo(TaskStatus::class, 'task_status_id');
    }
}
