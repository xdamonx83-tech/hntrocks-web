<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('arcade_user_stats', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('game_id')->constrained('arcade_games')->cascadeOnDelete();
            $table->string('mode', 16);
            $table->unsignedInteger('matches_played')->default(0);
            $table->unsignedInteger('wins')->default(0);
            $table->unsignedInteger('losses')->default(0);
            $table->unsignedInteger('draws')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'game_id', 'mode'], 'arcade_stats_user_game_mode_uq');
            $table->index(['game_id', 'mode', 'wins'], 'arcade_stats_leaderboard_idx');
        });

        Schema::create('arcade_match_finalizations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('match_id')->constrained('arcade_matches')->cascadeOnDelete();
            $table->timestamp('processed_at');
            $table->timestamps();

            $table->unique('match_id', 'arcade_finalization_match_uq');
        });

        Schema::create('arcade_match_rewards', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('match_id')->constrained('arcade_matches')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('reward_type', 32);
            $table->unsignedInteger('amount');
            $table->unsignedBigInteger('crown_transaction_id')->nullable()->index();
            $table->timestamps();

            $table->unique(['match_id', 'user_id', 'reward_type'], 'arcade_reward_match_user_type_uq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('arcade_match_rewards');
        Schema::dropIfExists('arcade_match_finalizations');
        Schema::dropIfExists('arcade_user_stats');
    }
};
