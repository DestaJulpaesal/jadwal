<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function status()
    {
        return $this->belongsTo(TaskStatus::class, 'task_status_id');
    }
}
