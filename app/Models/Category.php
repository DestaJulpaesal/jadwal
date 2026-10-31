<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'name', 'color'];

    public function displayColor(): string
    {
        return preg_match('/\A#[0-9a-f]{6}\z/i', $this->color ?? '') === 1
            ? $this->color
            : '#3B82F6';
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }
}
