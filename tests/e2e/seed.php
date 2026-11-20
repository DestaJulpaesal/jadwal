<?php

use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Hash;

require dirname(__DIR__, 2).'/vendor/autoload.php';

$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$user = User::factory()->create([
    'email' => 'playwright@example.test',
    'password' => Hash::make('Password123!'),
    'email_verified_at' => now(),
    'onboarding_dismissed_at' => now(),
]);

foreach (['E2E double-click task', 'E2E back-navigation task'] as $title) {
    $user->tasks()->create([
        'title' => $title,
        'recurrence' => 'daily',
    ]);
}
