<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('hunt_news_items')) {
            return;
        }

        Schema::create('hunt_news_items', function (Blueprint $table): void {
            $table->id();
            $table->char('source_hash', 40)->unique();
            $table->string('source_url', 2048);
            $table->string('source_slug', 190)->nullable()->index();
            $table->string('source_domain', 190)->default('huntshowdown.com')->index();
            $table->string('title', 240)->nullable();
            $table->text('excerpt')->nullable();
            $table->string('category', 80)->nullable()->index();
            $table->timestamp('source_published_at')->nullable()->index();
            $table->timestamp('discovered_at')->nullable()->index();
            $table->boolean('auto_publish_eligible')->default(false)->index();
            $table->string('status', 32)->default('discovered')->index();
            $table->foreignId('feed_post_id')->nullable()->constrained('feed_posts')->nullOnDelete();
            $table->foreignId('outbound_link_id')->nullable()->constrained('approved_outbound_links')->nullOnDelete();
            $table->timestamp('posted_at')->nullable()->index();
            $table->foreignId('posted_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('error_message')->nullable();
            $table->json('raw_meta')->nullable();
            $table->timestamps();

            $table->index(['status', 'source_published_at']);
            $table->index(['auto_publish_eligible', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hunt_news_items');
    }
};
