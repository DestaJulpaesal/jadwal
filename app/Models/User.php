<?php

namespace App\Models;

use Illuminate\Auth\MustVerifyEmail as MustVerifyEmailTrait;
use Illuminate\Contracts\Auth\MustVerifyEmail as MustVerifyEmailContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmailContract
{
    use HasFactory;
    use MustVerifyEmailTrait;
    use Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'role', 'timezone',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    // Cek jika pengguna adalah admin
    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }
}
