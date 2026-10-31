<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class CreateAdminUser extends Command
{
    protected $signature = 'admin:create {email} {--name=Admin}';

    protected $description = 'Create the first administrator account securely';

    public function handle(): int
    {
        $email = strtolower($this->argument('email'));
        $validator = Validator::make(
            ['email' => $email],
            ['email' => ['required', 'email', 'unique:users,email']]
        );

        if ($validator->fails()) {
            $this->error($validator->errors()->first('email'));

            return self::FAILURE;
        }

        $password = $this->secret('Password');
        $confirmation = $this->secret('Confirm password');
        $passwordValidator = Validator::make(
            ['password' => $password, 'password_confirmation' => $confirmation],
            ['password' => ['required', 'confirmed', Password::min(12)->mixedCase()->numbers()->symbols()]]
        );

        if ($passwordValidator->fails()) {
            $this->error($passwordValidator->errors()->first('password'));

            return self::FAILURE;
        }

        $admin = User::create([
            'name' => $this->option('name'),
            'email' => $email,
            'password' => Hash::make($password),
            'role' => 'admin',
        ]);
        $admin->forceFill([
            'email_verified_at' => now(),
            'is_active' => true,
        ])->save();

        $this->info('Akun admin berhasil dibuat dan telah diverifikasi.');

        return self::SUCCESS;
    }
}
