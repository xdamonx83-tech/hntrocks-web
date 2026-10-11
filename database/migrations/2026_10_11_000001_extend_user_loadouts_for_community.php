<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_loadouts', function (Blueprint $table): void {
            $table->boolean('is_published')->default(false)->index();
            $table->string('primary_ammo', 120)->nullable();
            $table->string('secondary_ammo', 120)->nullable();
            $table->index(['is_published', 'visibility', 'created_at'], 'loadouts_community_listing_index');
        });

        Schema::create('user_loadout_votes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_loadout_id')->constrained('user_loadouts')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->tinyInteger('value');
            $table->timestamps();
            $table->unique(['user_loadout_id', 'user_id'], 'loadout_vote_once_per_user');
        });

        Schema::create('user_loadout_comments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_loadout_id')->constrained('user_loadouts')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('body');
            $table->timestamps();
            $table->index(['user_loadout_id', 'created_at'], 'loadout_comments_recent_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_loadout_comments');
        Schema::dropIfExists('user_loadout_votes');

        Schema::table('user_loadouts', function (Blueprint $table): void {
            $table->dropIndex('loadouts_community_listing_index');
            $table->dropColumn(['is_published', 'primary_ammo', 'secondary_ammo']);
        });
    }
};
