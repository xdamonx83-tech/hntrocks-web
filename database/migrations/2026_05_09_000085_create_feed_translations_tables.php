<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('feed_posts', function (Blueprint $table): void {
            $table->string('source_language', 8)->nullable()->after('body')->index();
        });

        Schema::table('feed_comments', function (Blueprint $table): void {
            $table->string('source_language', 8)->nullable()->after('body')->index();
        });

        Schema::create('feed_post_translations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('feed_post_id')->constrained('feed_posts')->cascadeOnDelete();
            $table->string('locale', 8);
            $table->string('source_locale', 8)->nullable();
            $table->string('provider', 60)->default('openai');
            $table->text('translated_body');
            $table->timestamps();

            $table->unique(['feed_post_id', 'locale']);
            $table->index(['locale', 'created_at']);
        });

        Schema::create('feed_comment_translations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('feed_comment_id')->constrained('feed_comments')->cascadeOnDelete();
            $table->string('locale', 8);
            $table->string('source_locale', 8)->nullable();
            $table->string('provider', 60)->default('openai');
            $table->text('translated_body');
            $table->timestamps();

            $table->unique(['feed_comment_id', 'locale']);
            $table->index(['locale', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feed_comment_translations');
        Schema::dropIfExists('feed_post_translations');

        Schema::table('feed_comments', function (Blueprint $table): void {
            $table->dropColumn('source_language');
        });

        Schema::table('feed_posts', function (Blueprint $table): void {
            $table->dropColumn('source_language');
        });
    }
};
