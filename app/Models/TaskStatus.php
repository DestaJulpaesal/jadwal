<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskStatus extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'is_completed'];

    protected $casts = ['is_completed' => 'boolean'];

    public function setNameAttribute(string $value): void
    {
        $this->attributes['name'] = $value;
        if ($value === 'Selesai') {
            $this->attributes['is_completed'] = true;
        }
    }

    public function tasks()
    {
        return $this->hasMany(Task::class, 'task_status_id');
    }
}
