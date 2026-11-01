<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Carbon;

class UserClock
{
    public function today(User $user): Carbon
    {
        return Carbon::now($user->timezone)->startOfDay();
    }

    public function date(User $user): string
    {
        return $this->today($user)->toDateString();
    }
}
