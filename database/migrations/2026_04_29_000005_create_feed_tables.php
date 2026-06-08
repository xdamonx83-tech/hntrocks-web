<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feed_posts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('body')->nullable();
            $table->string('visibility', 24)->default('public')->index();
            $table->string('status', 24)->default('published')->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'created_at']);
        });

        Schema::create('feed_post_media', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('feed_post_id')->constrained('feed_posts')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('disk', 40)->default('public');
            $table->string('path');
            $table->string('mime_type', 120)->nullable();
            $table->string('original_name')->nullable();
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['feed_post_id', 'sort_order']);
        });

        Schema::create('feed_comments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('feed_post_id')->constrained('feed_posts')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('feed_comments')->nullOnDelete();
            $table->text('body');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['feed_post_id', 'created_at']);
        });

        Schema::create('feed_reactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('feed_post_id')->constrained('feed_posts')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 32)->default('like');
            $table->timestamps();

            $table->unique(['feed_post_id', 'user_id']);
            $table->index(['user_id', 'created_at']);
        });

        Schema::create('feed_bookmarks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('feed_post_id')->constrained('feed_posts')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['feed_post_id', 'user_id']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feed_bookmarks');
        Schema::dropIfExists('feed_reactions');
        Schema::dropIfExists('feed_comments');
        Schema::dropIfExists('feed_post_media');
        Schema::dropIfExists('feed_posts');
    }
};
