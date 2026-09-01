<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('arcade_game_releases', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('game_id')->constrained('arcade_games')->cascadeOnDelete();
            $table->string('version', 64);
            $table->string('status', 20)->default('draft');
            $table->string('entrypoint_url', 2048);
            $table->string('manifest_url', 2048)->nullable();
            $table->char('integrity_sha256', 64);
            $table->json('manifest');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('retired_at')->nullable();
            $table->timestamps();

            $table->unique(['game_id', 'version']);
            $table->index(['game_id', 'status', 'published_at']);
        });

        Schema::create('arcade_launch_tickets', function (Blueprint $table): void {
            $table->id();
            $table->char('token_hash', 64)->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('game_id')->constrained('arcade_games')->cascadeOnDelete();
            $table->foreignId('release_id')->constrained('arcade_game_releases')->cascadeOnDelete();
            $table->foreignId('match_id')->nullable()->constrained('arcade_matches')->nullOnDelete();
            $table->string('client', 20);
            $table->timestamp('expires_at');
            $table->timestamp('consumed_at')->nullable();
            $table->timestamps();

            $table->index(['game_id', 'expires_at']);
            $table->index(['user_id', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('arcade_launch_tickets');
        Schema::dropIfExists('arcade_game_releases');
    }
};
