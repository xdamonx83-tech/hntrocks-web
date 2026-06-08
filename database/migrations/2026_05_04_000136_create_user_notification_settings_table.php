<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_notification_settings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->boolean('feed_comments')->default(true);
            $table->boolean('feed_reactions')->default(true);
            $table->boolean('friends')->default(true);
            $table->boolean('teams')->default(true);
            $table->boolean('lfg')->default(true);
            $table->boolean('gamification')->default(true);
            $table->boolean('moments')->default(true);
            $table->boolean('cups')->default(true);
            $table->boolean('referrals')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_notification_settings');
    }
};
