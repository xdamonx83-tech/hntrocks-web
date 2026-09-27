<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('news_articles', function (Blueprint $table): void {
            $table->id();
            $table->string('status', 20)->default('draft')->index();
            $table->string('category_key', 80)->nullable()->index();
            $table->json('tags')->nullable();
            $table->foreignId('hero_media_asset_id')->nullable()->constrained('media_assets')->nullOnDelete();
            $table->boolean('featured')->default(false)->index();
            $table->boolean('comments_enabled')->default(false);
            $table->timestamp('scheduled_at')->nullable()->index();
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamp('archived_at')->nullable();
            $table->unsignedInteger('lock_version')->default(1);
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('published_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'scheduled_at']);
            $table->index(['status', 'published_at']);
        });

        Schema::create('news_article_translations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('news_article_id')->constrained('news_articles')->cascadeOnDelete();
            $table->string('locale', 5);
            $table->string('slug', 190)->nullable();
            $table->string('title', 240)->nullable();
            $table->text('excerpt')->nullable();
            $table->json('content_json')->nullable();
            $table->string('seo_title', 240)->nullable();
            $table->text('seo_description')->nullable();
            $table->string('canonical_url', 2048)->nullable();
            $table->timestamps();

            $table->unique(['news_article_id', 'locale']);
            $table->unique(['locale', 'slug']);
            $table->index(['locale', 'title']);
        });

        Schema::create('news_article_revisions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('news_article_id')->constrained('news_articles')->cascadeOnDelete();
            $table->unsignedInteger('revision_number');
            $table->unsignedInteger('lock_version');
            $table->string('revision_type', 24)->default('autosave');
            $table->json('snapshot_json');
            $table->foreignId('editor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['news_article_id', 'revision_number']);
            $table->index(['news_article_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('news_article_revisions');
        Schema::dropIfExists('news_article_translations');
        Schema::dropIfExists('news_articles');
    }
};
