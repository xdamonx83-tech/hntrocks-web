<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('moments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('media_asset_id')->constrained('media_assets')->cascadeOnDelete();
            $table->foreignId('cover_media_asset_id')->nullable()->constrained('media_assets')->nullOnDelete();
            $table->string('caption', 220)->nullable();
            $table->text('description')->nullable();
            $table->string('visibility', 40)->default('public')->index();
            $table->string('status', 40)->default('published')->index();
            $table->string('processing_status', 40)->default('ready')->index();
            $table->unsignedInteger('trim_start_seconds')->nullable();
            $table->unsignedInteger('trim_end_seconds')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->unsignedInteger('views_count')->default(0);
            $table->unsignedInteger('likes_count')->default(0);
            $table->unsignedInteger('comments_count')->default(0);
            $table->unsignedInteger('bookmarks_count')->default(0);
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'visibility', 'published_at']);
        });

        Schema::create('moment_comments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('moment_id')->constrained('moments')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('body');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('moment_reactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('moment_id')->constrained('moments')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 30)->default('like');
            $table->timestamps();

            $table->unique(['moment_id', 'user_id', 'type']);
        });

        Schema::create('moment_bookmarks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('moment_id')->constrained('moments')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['moment_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('moment_bookmarks');
        Schema::dropIfExists('moment_reactions');
        Schema::dropIfExists('moment_comments');
        Schema::dropIfExists('moments');
    }
};
