<?php

namespace App\Models;

use App\Models\Concerns\CastsBusinessDates;
use Illuminate\Auth\MustVerifyEmail as MustVerifyEmailTrait;
use Illuminate\Contracts\Auth\MustVerifyEmail as MustVerifyEmailContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmailContract
{
    use CastsBusinessDates;
    use HasFactory;
    use MustVerifyEmailTrait;
    use Notifiable;

    protected array $businessDateColumns = ['digest_last_sent_on'];

    protected $fillable = [
        'name', 'email', 'password', 'role', 'timezone', 'digest_enabled',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active' => 'boolean',
        'digest_enabled' => 'boolean',
        'digest_last_sent_on' => 'date',
        'onboarding_dismissed_at' => 'datetime',
    ];

    // Cek jika pengguna adalah admin
    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function habits(): HasMany
    {
        return $this->hasMany(Habit::class);
    }

    public function habitLogs(): HasMany
    {
        return $this->hasMany(HabitLog::class);
    }

    public function goals(): HasMany
    {
        return $this->hasMany(Goal::class);
    }

    public function goalUpdates(): HasMany
    {
        return $this->hasMany(GoalUpdate::class);
    }

    public function wallets(): HasMany
    {
        return $this->hasMany(Wallet::class);
    }

    public function moneyCategories(): HasMany
    {
        return $this->hasMany(MoneyCategory::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function obligations(): HasMany
    {
        return $this->hasMany(Obligation::class);
    }

    public function obligationPayments(): HasMany
    {
        return $this->hasMany(ObligationPayment::class);
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }

    public function loanRepayments(): HasMany
    {
        return $this->hasMany(LoanRepayment::class);
    }
}
