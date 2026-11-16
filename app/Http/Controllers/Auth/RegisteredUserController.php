<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use App\Services\MoneyCategoryPresetSeeder;
use App\Support\Feedback;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request, MoneyCategoryPresetSeeder $presetSeeder): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Password::defaults(), 'different:email', 'different:name'],
        ], __('validation.catalog.registration'), [
            'name' => 'nama',
            'email' => 'email',
            'password' => 'kata sandi',
        ]);

        $user = DB::transaction(function () use ($request, $presetSeeder) {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);
            $presetSeeder->seedFor($user);

            return $user;
        });

        event(new Registered($user));

        Auth::login($user);

        return Feedback::success(redirect(RouteServiceProvider::HOME), __('ui.login.welcome', ['name' => $user->name]));
    }
}
