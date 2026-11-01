<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\Goal;
use App\Models\GoalUpdate;
use App\Models\Habit;
use App\Models\HabitLog;
use App\Models\Loan;
use App\Models\LoanRepayment;
use App\Models\MoneyCategory;
use App\Models\Obligation;
use App\Models\ObligationPayment;
use App\Models\Task;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Policies\CategoryPolicy;
use App\Policies\OwnedModelPolicy;
use App\Policies\TaskPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Task::class => TaskPolicy::class,
        Category::class => CategoryPolicy::class,
        Habit::class => OwnedModelPolicy::class,
        HabitLog::class => OwnedModelPolicy::class,
        Goal::class => OwnedModelPolicy::class,
        GoalUpdate::class => OwnedModelPolicy::class,
        Wallet::class => OwnedModelPolicy::class,
        MoneyCategory::class => OwnedModelPolicy::class,
        Transaction::class => OwnedModelPolicy::class,
        Obligation::class => OwnedModelPolicy::class,
        ObligationPayment::class => OwnedModelPolicy::class,
        Loan::class => OwnedModelPolicy::class,
        LoanRepayment::class => OwnedModelPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        //
    }
}
