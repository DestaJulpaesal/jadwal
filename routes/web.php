<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\User\TaskController as UserTaskController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/
Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::middleware(['auth', 'active', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'index'])->name('dashboard');
    Route::get('/users', [AdminController::class, 'index'])->name('users.index');
    Route::patch('/users/{user}/status', [AdminController::class, 'toggleStatus'])->name('users.status');
    Route::delete('/users/{user}', [AdminController::class, 'destroy'])->name('users.destroy');
});

$dashboardMiddleware = ['auth', 'active'];

if (config('auth.email_verification')) {
    $dashboardMiddleware[] = 'verified';
}

Route::middleware($dashboardMiddleware)->group(function () {
    Route::prefix('dashboard')->name('user.')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('dashboard');
        Route::get('/calendar', [UserController::class, 'calendar'])->name('calendar');
        Route::patch('tasks/{task}/complete', [UserTaskController::class, 'complete'])->name('tasks.complete');
        Route::resource('tasks', UserTaskController::class)->except(['show']);
    });
});

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
