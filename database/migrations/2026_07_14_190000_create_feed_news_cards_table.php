<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feed_news_cards', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('feed_post_id')->unique()->constrained('feed_posts')->cascadeOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('publisher', 32)->index();
            $table->string('badge', 40)->default('News');
            $table->string('kicker', 80)->nullable();
            $table->string('headline', 190);
            $table->json('highlights')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feed_news_cards');
    }
};
