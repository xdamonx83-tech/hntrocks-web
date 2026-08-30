<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('arcade_matches', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('game_id')->constrained('arcade_games');
            $table->string('mode', 20);
            $table->string('status', 30)->default('waiting_ready');
            $table->json('state')->nullable();
            $table->unsignedInteger('version')->default(0);
            $table->unsignedSmallInteger('current_seat')->nullable();
            $table->unsignedSmallInteger('winner_seat')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'expires_at']);
        });

        Schema::create('arcade_match_players', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('match_id')->constrained('arcade_matches')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedSmallInteger('seat');
            $table->string('status', 20)->default('joined');
            $table->timestamp('joined_at');
            $table->timestamp('ready_at')->nullable();
            $table->timestamp('left_at')->nullable();
            $table->string('result', 20)->nullable();
            $table->timestamps();
            $table->unique(['match_id', 'seat']);
            $table->unique(['match_id', 'user_id']);
        });

        Schema::create('arcade_match_moves', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('match_id')->constrained('arcade_matches')->cascadeOnDelete();
            $table->foreignId('match_player_id')->constrained('arcade_match_players');
            $table->unsignedInteger('sequence');
            $table->string('client_move_id', 100);
            $table->string('move_type', 50);
            $table->json('payload');
            $table->unsignedInteger('state_version_before');
            $table->unsignedInteger('state_version_after');
            $table->timestamps();
            $table->unique(['match_id', 'sequence']);
            $table->unique(['match_id', 'client_move_id']);
        });

        Schema::create('arcade_invitations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('game_id')->constrained('arcade_games');
            $table->foreignId('inviter_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('invitee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('mode', 20);
            $table->string('status', 20)->default('pending');
            $table->foreignId('match_id')->nullable()->constrained('arcade_matches')->nullOnDelete();
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('declined_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
            $table->index(['invitee_id', 'status']);
            $table->index(['inviter_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('arcade_invitations');
        Schema::dropIfExists('arcade_match_moves');
        Schema::dropIfExists('arcade_match_players');
        Schema::dropIfExists('arcade_matches');
    }
};
