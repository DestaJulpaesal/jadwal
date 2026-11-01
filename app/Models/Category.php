<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'color'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function displayColor(): string
    {
        return preg_match('/\A#[0-9a-f]{6}\z/i', $this->color ?? '') === 1
            ? $this->color
            : '#3B82F6';
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }
}
