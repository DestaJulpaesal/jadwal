<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('digest_enabled')->default(false);
            $table->date('digest_last_sent_on')->nullable();
            $table->dateTime('onboarding_dismissed_at')->nullable();
        });

        Schema::create('habits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title', 120);
            $table->string('description', 500)->nullable();
            $table->string('frequency', 20);
            $table->json('weekdays')->nullable();
            $table->unsignedTinyInteger('weekly_target')->nullable();
            $table->unsignedInteger('goal_value')->nullable();
            $table->string('goal_unit', 20)->nullable();
            $table->string('preferred_time', 5)->nullable();
            $table->date('starts_on');
            $table->date('ends_on')->nullable();
            $table->dateTime('archived_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'archived_at']);
            $table->index(['user_id', 'starts_on']);
        });

        Schema::create('habit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('habit_id')->constrained()->cascadeOnDelete();
            $table->date('log_date');
            $table->string('status', 20);
            $table->unsignedInteger('value')->nullable();
            $table->string('note', 255)->nullable();
            $table->timestamps();
            $table->unique(['habit_id', 'log_date']);
            $table->index(['user_id', 'log_date']);
        });

        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 60);
            $table->string('type', 20);
            $table->bigInteger('opening_balance')->default(0);
            $table->dateTime('archived_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'archived_at']);
        });

        Schema::create('money_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 60);
            $table->string('type', 20);
            $table->dateTime('archived_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'type', 'name']);
        });

        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('wallet_id')->constrained()->restrictOnDelete();
            $table->foreignId('to_wallet_id')->nullable()->constrained('wallets')->restrictOnDelete();
            $table->string('type', 20);
            $table->unsignedBigInteger('amount');
            $table->foreignId('money_category_id')->nullable()->constrained()->nullOnDelete();
            $table->date('occurred_on');
            $table->string('note', 255)->nullable();
            $table->string('source_type', 40)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'occurred_on']);
            $table->index('wallet_id');
            $table->index('to_wallet_id');
            $table->index(['source_type', 'source_id']);
        });

        Schema::create('obligations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 20);
            $table->string('title', 120);
            $table->unsignedBigInteger('amount');
            $table->foreignId('wallet_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('money_category_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedTinyInteger('due_day');
            $table->date('starts_on');
            $table->unsignedSmallInteger('installments_total')->nullable();
            $table->unsignedTinyInteger('remind_days_before')->default(3);
            $table->string('note', 255)->nullable();
            $table->dateTime('archived_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'starts_on']);
            $table->index(['user_id', 'due_day']);
        });

        Schema::create('obligation_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('obligation_id')->constrained()->cascadeOnDelete();
            $table->date('due_on');
            $table->date('paid_on');
            $table->unsignedBigInteger('amount_paid');
            $table->foreignId('transaction_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
            $table->unique(['obligation_id', 'due_on']);
            $table->index(['user_id', 'due_on']);
        });

        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('direction', 20);
            $table->string('counterparty', 80);
            $table->unsignedBigInteger('amount');
            $table->date('loan_date');
            $table->date('due_on')->nullable();
            $table->foreignId('wallet_id')->nullable()->constrained()->nullOnDelete();
            $table->string('note', 255)->nullable();
            $table->dateTime('archived_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'due_on']);
            $table->index(['user_id', 'archived_at']);
        });

        Schema::create('loan_repayments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('loan_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('amount');
            $table->date('paid_on');
            $table->foreignId('wallet_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('transaction_id')->nullable()->constrained()->nullOnDelete();
            $table->string('note', 255)->nullable();
            $table->timestamps();
            $table->index(['user_id', 'paid_on']);
        });

        Schema::create('goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title', 120);
            $table->string('description', 500)->nullable();
            $table->string('type', 20);
            $table->unsignedBigInteger('target_value');
            $table->unsignedBigInteger('start_value')->default(0);
            $table->string('unit', 40)->nullable();
            $table->date('due_on')->nullable();
            $table->string('status', 20)->default('active');
            $table->foreignId('habit_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('wallet_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
            $table->index(['user_id', 'due_on']);
            $table->index(['user_id', 'status']);
        });

        Schema::create('goal_updates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('goal_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('amount');
            $table->date('recorded_on');
            $table->string('note', 255)->nullable();
            $table->timestamps();
            $table->index(['user_id', 'recorded_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goal_updates');
        Schema::dropIfExists('goals');
        Schema::dropIfExists('loan_repayments');
        Schema::dropIfExists('loans');
        Schema::dropIfExists('obligation_payments');
        Schema::dropIfExists('obligations');
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('money_categories');
        Schema::dropIfExists('wallets');
        Schema::dropIfExists('habit_logs');
        Schema::dropIfExists('habits');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['digest_enabled', 'digest_last_sent_on', 'onboarding_dismissed_at']);
        });
    }
};
