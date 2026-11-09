<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Services\ActivityStreak;
use App\Support\UserClock;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StreakController extends Controller
{
    public function __invoke(Request $request, UserClock $clock, ActivityStreak $activityStreak): View
    {
        return view('user.streak', $activityStreak->summary(
            $request->user(),
            $clock->date($request->user())
        ));
    }
}
