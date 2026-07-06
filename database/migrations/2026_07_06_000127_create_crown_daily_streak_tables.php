<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crown_daily_streaks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('current_streak')->default(0);
            $table->date('last_claimed_on')->nullable();
            $table->timestamp('last_claimed_at')->nullable();
            $table->date('dismissed_on')->nullable();
            $table->unsignedInteger('total_claims')->default(0);
            $table->unsignedInteger('longest_streak')->default(0);
            $table->timestamps();
        });

        Schema::create('crown_daily_streak_claims', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->date('claim_date');
            $table->unsignedInteger('streak_day');
            $table->unsignedInteger('amount');
            $table->foreignId('transaction_id')->nullable()->constrained('crown_transactions')->nullOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'claim_date']);
            $table->index(['claim_date', 'streak_day']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crown_daily_streak_claims');
        Schema::dropIfExists('crown_daily_streaks');
    }
};
