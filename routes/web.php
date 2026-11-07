<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminCategoryController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\User\GoalController;
use App\Http\Controllers\User\HabitController;
use App\Http\Controllers\User\LoanController;
use App\Http\Controllers\User\MoneyController;
use App\Http\Controllers\User\ObligationController;
use App\Http\Controllers\User\ReportController;
use App\Http\Controllers\User\SearchController;
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

if (app()->environment('local')) {
    Route::view('/styleguide', 'styleguide')->name('styleguide');
}

Route::middleware(['auth', 'active', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'index'])->name('dashboard');
    Route::get('/users', [AdminController::class, 'index'])->name('users.index');
    Route::get('/users/{user}', [AdminController::class, 'show'])->name('users.show');
    Route::get('/categories', [AdminCategoryController::class, 'index'])->name('categories.index');
    Route::post('/categories', [AdminCategoryController::class, 'store'])->name('categories.store');
    Route::patch('/categories/{category}', [AdminCategoryController::class, 'update'])->name('categories.update');
    Route::delete('/categories/{category}', [AdminCategoryController::class, 'destroy'])->name('categories.destroy');
    Route::patch('/users/{user}/status', [AdminController::class, 'toggleStatus'])->name('users.status');
    Route::patch('/users/{user}/role', [AdminController::class, 'updateRole'])->name('users.role');
    Route::post('/users/{user}/password-reset', [AdminController::class, 'sendPasswordResetLink'])
        ->middleware('throttle:5,1')
        ->name('users.password-reset');
    Route::delete('/users/{user}', [AdminController::class, 'destroy'])->name('users.destroy');
});

$dashboardMiddleware = ['auth', 'active'];

if (config('auth.email_verification')) {
    $dashboardMiddleware[] = 'verified';
}

Route::middleware($dashboardMiddleware)->group(function () {
    Route::prefix('dashboard')->name('user.')->group(function () {
        Route::post('/onboarding/dismiss', [UserController::class, 'dismissOnboarding'])->middleware('throttle:60,1')->name('onboarding.dismiss');
        Route::get('/', [UserController::class, 'index'])->name('dashboard');
        Route::get('/calendar', [UserController::class, 'calendar'])->name('calendar');
        Route::view('/more', 'user.more.index')->name('more');
        Route::get('/search', SearchController::class)->name('search');
        Route::patch('tasks/{task}/complete', [UserTaskController::class, 'complete'])->name('tasks.complete');
        Route::resource('tasks', UserTaskController::class)->except(['show']);
        Route::post('habits/{habit}/check-in', [HabitController::class, 'checkIn'])->middleware('throttle:60,1')->name('habits.check-in');
        Route::delete('habits/{habit}/check-in', [HabitController::class, 'cancelCheckIn'])->middleware('throttle:60,1')->name('habits.check-in.cancel');
        Route::patch('habits/{habit}/archive', [HabitController::class, 'archive'])->middleware('throttle:60,1')->name('habits.archive');
        Route::resource('habits', HabitController::class)->except(['show', 'destroy'])->middleware('throttle:60,1');
        Route::get('/money', [MoneyController::class, 'index'])->name('money.index');
        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::post('/money/wallets', [MoneyController::class, 'storeWallet'])->middleware('throttle:60,1')->name('money.wallets.store');
        Route::patch('/money/wallets/{wallet}', [MoneyController::class, 'updateWallet'])->middleware('throttle:60,1')->name('money.wallets.update');
        Route::patch('/money/wallets/{wallet}/archive', [MoneyController::class, 'archiveWallet'])->middleware('throttle:60,1')->name('money.wallets.archive');
        Route::post('/money/transactions', [MoneyController::class, 'storeTransaction'])->middleware('throttle:60,1')->name('money.transactions.store');
        Route::get('/money/transactions/{transaction}/edit', [MoneyController::class, 'editTransaction'])->name('money.transactions.edit');
        Route::patch('/money/transactions/{transaction}', [MoneyController::class, 'updateTransaction'])->middleware('throttle:60,1')->name('money.transactions.update');
        Route::post('/money/categories', [MoneyController::class, 'storeCategory'])->middleware('throttle:60,1')->name('money.categories.store');
        Route::patch('/money/categories/{moneyCategory}', [MoneyController::class, 'updateCategory'])->middleware('throttle:60,1')->name('money.categories.update');
        Route::get('/obligations', [ObligationController::class, 'index'])->name('obligations.index');
        Route::post('/obligations', [ObligationController::class, 'store'])->middleware('throttle:60,1')->name('obligations.store');
        Route::get('/obligations/{obligation}/edit', [ObligationController::class, 'edit'])->name('obligations.edit');
        Route::patch('/obligations/{obligation}', [ObligationController::class, 'update'])->middleware('throttle:60,1')->name('obligations.update');
        Route::post('/obligations/{obligation}/payments', [ObligationController::class, 'pay'])->middleware('throttle:60,1')->name('obligations.pay');
        Route::delete('/obligations/{obligation}/payments/{payment}', [ObligationController::class, 'cancelPayment'])->middleware('throttle:60,1')->name('obligations.payments.cancel');
        Route::get('/loans', [LoanController::class, 'index'])->name('loans.index');
        Route::post('/loans', [LoanController::class, 'store'])->middleware('throttle:60,1')->name('loans.store');
        Route::get('/loans/{loan}/edit', [LoanController::class, 'edit'])->name('loans.edit');
        Route::patch('/loans/{loan}', [LoanController::class, 'update'])->middleware('throttle:60,1')->name('loans.update');
        Route::post('/loans/{loan}/repayments', [LoanController::class, 'repay'])->middleware('throttle:60,1')->name('loans.repay');
        Route::delete('/loans/{loan}/repayments/{repayment}', [LoanController::class, 'cancelRepayment'])->middleware('throttle:60,1')->name('loans.repayments.cancel');
        Route::get('/goals', [GoalController::class, 'index'])->name('goals.index');
        Route::get('/goals/create', [GoalController::class, 'create'])->name('goals.create');
        Route::get('/goals/{goal}/edit', [GoalController::class, 'edit'])->name('goals.edit');
        Route::post('/goals', [GoalController::class, 'store'])->middleware('throttle:60,1')->name('goals.store');
        Route::put('/goals/{goal}', [GoalController::class, 'update'])->middleware('throttle:60,1')->name('goals.update');
        Route::post('/goals/{goal}/updates', [GoalController::class, 'updateProgress'])->middleware('throttle:60,1')->name('goals.updates.store');
        Route::patch('/goals/{goal}/archive', [GoalController::class, 'archive'])->middleware('throttle:60,1')->name('goals.archive');
        Route::patch('/goals/{goal}/achieve', [GoalController::class, 'markAchieved'])->middleware('throttle:60,1')->name('goals.achieve');
    });
});

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/settings', [SettingsController::class, 'edit'])->name('settings.edit');
    Route::patch('/settings', [SettingsController::class, 'update'])->name('settings.update');
    Route::get('/profile/photo', [ProfileController::class, 'photo'])->name('profile.photo');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
