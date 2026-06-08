<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('headline', 120)->nullable();
            $table->text('bio')->nullable();
            $table->string('platform', 40)->nullable();
            $table->string('playstyle', 60)->nullable();
            $table->string('region', 60)->nullable();
            $table->string('language', 40)->nullable();
            $table->string('hunt_role', 60)->nullable();
            $table->string('discord_name', 80)->nullable();
            $table->string('steam_url')->nullable();
            $table->string('twitch_url')->nullable();
            $table->string('youtube_url')->nullable();
            $table->boolean('is_lfg_available')->default(false);
            $table->string('profile_visibility', 20)->default('public');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_profiles');
    }
};
