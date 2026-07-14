<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feed_cup_cards', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('feed_post_id')->unique()->constrained('feed_posts')->cascadeOnDelete();
            $table->foreignId('cup_id')->unique()->constrained('cups')->cascadeOnDelete();
            $table->foreignId('published_by_user_id')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feed_cup_cards');
    }
};
