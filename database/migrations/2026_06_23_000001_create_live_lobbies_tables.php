<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('live_lobbies', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('creator_id')->constrained('users')->cascadeOnDelete();
            $table->string('mode', 10);
            $table->unsignedTinyInteger('slots_total');
            $table->unsignedTinyInteger('slots_filled')->default(1);
            $table->string('platform', 20);
            $table->string('crossplay_pool', 20);
            $table->string('region', 60)->nullable();
            $table->string('language', 40)->nullable();
            $table->boolean('voice_required')->default(false);
            $table->string('playstyle', 60)->nullable();
            $table->text('note')->nullable();
            $table->string('lobby_code', 64)->nullable();
            $table->string('steam_id', 100)->nullable();
            $table->string('psn_id', 100)->nullable();
            $table->string('xbox_gamertag', 100)->nullable();
            $table->string('discord_handle', 100)->nullable();
            $table->string('status', 20)->default('open');
            $table->timestamp('expires_at');
            $table->timestamp('full_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'expires_at']);
            $table->index(['crossplay_pool', 'platform', 'region', 'language'], 'live_lobbies_discovery_index');
            $table->index(['creator_id', 'status']);
        });

        Schema::create('live_lobby_members', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('live_lobby_id')->constrained('live_lobbies')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role', 20);
            $table->string('platform', 20)->nullable();
            $table->string('platform_handle', 100)->nullable();
            $table->timestamp('joined_at');
            $table->timestamp('left_at')->nullable();
            $table->timestamps();

            $table->index(['live_lobby_id', 'user_id']);
            $table->index(['user_id', 'left_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('live_lobby_members');
        Schema::dropIfExists('live_lobbies');
    }
};
