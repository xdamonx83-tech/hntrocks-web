<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('news_article_interactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('news_article_id')->constrained('news_articles')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->boolean('liked')->default(false);
            $table->boolean('saved')->default(false);
            $table->timestamps();
            $table->unique(['news_article_id', 'user_id']);
        });

        Schema::create('news_article_comments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('news_article_id')->constrained('news_articles')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('news_article_comments')->cascadeOnDelete();
            $table->text('body');
            $table->softDeletes();
            $table->timestamps();
            $table->index(['news_article_id', 'created_at']);
        });

        Schema::create('news_article_comment_likes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('news_article_comment_id')->constrained('news_article_comments')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['news_article_comment_id', 'user_id'], 'news_comment_like_user_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('news_article_comment_likes');
        Schema::dropIfExists('news_article_comments');
        Schema::dropIfExists('news_article_interactions');
    }
};
