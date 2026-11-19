<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserAuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'action',
        'ip_address',
        'user_agent',
        'created_at',
    ];
}
