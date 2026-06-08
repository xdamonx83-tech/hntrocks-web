<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crown_wallets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('balance')->default(0);
            $table->unsignedInteger('lifetime_earned')->default(0);
            $table->unsignedInteger('lifetime_spent')->default(0);
            $table->timestamp('last_daily_login_reward_at')->nullable();
            $table->timestamps();
        });

        Schema::create('crown_transactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('wallet_id')->nullable()->constrained('crown_wallets')->nullOnDelete();
            $table->string('type', 32)->index();
            $table->string('action', 100)->nullable()->index();
            $table->integer('amount');
            $table->integer('balance_after')->default(0);
            $table->nullableMorphs('source');
            $table->string('description', 255)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['user_id', 'action']);
        });

        Schema::create('crown_reward_claims', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('action', 100);
            $table->date('reward_date');
            $table->unsignedInteger('claims_count')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'action', 'reward_date']);
            $table->index(['action', 'reward_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crown_reward_claims');
        Schema::dropIfExists('crown_transactions');
        Schema::dropIfExists('crown_wallets');
    }
};
