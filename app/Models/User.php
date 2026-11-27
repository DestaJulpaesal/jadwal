<?php

namespace App\Models;

use App\Models\Concerns\CastsBusinessDates;
use Illuminate\Auth\MustVerifyEmail as MustVerifyEmailTrait;
use Illuminate\Contracts\Auth\MustVerifyEmail as MustVerifyEmailContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable implements MustVerifyEmailContract
{
    use CastsBusinessDates;
    use HasFactory;
    use MustVerifyEmailTrait;
    use Notifiable;

    protected $attributes = [
        'timezone' => 'Asia/Jakarta',
    ];

    protected array $businessDateColumns = ['digest_last_sent_on', 'push_last_sent_on'];

    protected $fillable = [
        'name', 'email', 'password', 'timezone', 'digest_enabled', 'push_enabled',
        'week_starts_on', 'theme', 'digest_time', 'digest_days',
        'hide_financial_amounts', 'dashboard_sections',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active' => 'boolean',
        'digest_enabled' => 'boolean',
        'push_enabled' => 'boolean',
        'digest_days' => 'array',
        'hide_financial_amounts' => 'boolean',
        'dashboard_sections' => 'array',
        'digest_last_sent_on' => 'date',
        'push_last_sent_on' => 'date',
        'money_presets_seeded_at' => 'datetime',
        'onboarding_dismissed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::deleting(function (User $user): void {
            $user->categories()->delete();
        });

        static::deleted(function (User $user): void {
            $path = $user->profile_photo_path;
            $disk = Storage::disk('local');

            if ($path && $disk->exists($path) && ! $disk->delete($path)) {
                Log::warning('A deleted user profile photo could not be removed from local storage.', [
                    'user_id' => $user->id,
                ]);
            }
        });
    }

    // Cek jika pengguna adalah admin
    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    /** @return HasMany<Task, $this> */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /** @return HasMany<Category, $this> */
    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    /** @return HasMany<Habit, $this> */
    public function habits(): HasMany
    {
        return $this->hasMany(Habit::class);
    }

    /** @return HasMany<HabitLog, $this> */
    public function habitLogs(): HasMany
    {
        return $this->hasMany(HabitLog::class);
    }

    /** @return HasMany<Goal, $this> */
    public function goals(): HasMany
    {
        return $this->hasMany(Goal::class);
    }

    /** @return HasMany<GoalUpdate, $this> */
    public function goalUpdates(): HasMany
    {
        return $this->hasMany(GoalUpdate::class);
    }

    /** @return HasMany<Wallet, $this> */
    public function wallets(): HasMany
    {
        return $this->hasMany(Wallet::class);
    }

    /** @return HasMany<MoneyCategory, $this> */
    public function moneyCategories(): HasMany
    {
        return $this->hasMany(MoneyCategory::class);
    }

    /** @return HasMany<Budget, $this> */
    public function budgets(): HasMany
    {
        return $this->hasMany(Budget::class);
    }

    /** @return HasMany<Transaction, $this> */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /** @return HasMany<Obligation, $this> */
    public function obligations(): HasMany
    {
        return $this->hasMany(Obligation::class);
    }

    /** @return HasMany<ObligationPayment, $this> */
    public function obligationPayments(): HasMany
    {
        return $this->hasMany(ObligationPayment::class);
    }

    /** @return HasMany<Loan, $this> */
    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }

    /** @return HasMany<LoanRepayment, $this> */
    public function loanRepayments(): HasMany
    {
        return $this->hasMany(LoanRepayment::class);
    }

    /** @return HasMany<UserAuditLog, $this> */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(UserAuditLog::class);
    }

    /** @return HasMany<PushSubscription, $this> */
    public function pushSubscriptions(): HasMany
    {
        return $this->hasMany(PushSubscription::class);
    }
}
