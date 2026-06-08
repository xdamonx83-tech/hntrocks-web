<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('feed_post_polls')) {
            Schema::create('feed_post_polls', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('feed_post_id')->unique()->constrained('feed_posts')->cascadeOnDelete();
                $table->string('question', 180)->nullable();
                $table->boolean('allows_multiple')->default(false);
                $table->timestamp('closed_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('feed_post_poll_options')) {
            Schema::create('feed_post_poll_options', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('feed_post_poll_id')->constrained('feed_post_polls')->cascadeOnDelete();
                $table->string('body', 180);
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->timestamps();

                $table->index(['feed_post_poll_id', 'sort_order']);
            });
        }

        if (! Schema::hasTable('feed_post_poll_votes')) {
            Schema::create('feed_post_poll_votes', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('feed_post_poll_id')->constrained('feed_post_polls')->cascadeOnDelete();
                $table->foreignId('feed_post_poll_option_id')->constrained('feed_post_poll_options')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['feed_post_poll_id', 'user_id']);
                $table->index(['feed_post_poll_option_id', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('feed_post_poll_votes');
        Schema::dropIfExists('feed_post_poll_options');
        Schema::dropIfExists('feed_post_polls');
    }
};
