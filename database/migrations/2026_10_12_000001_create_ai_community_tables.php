<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('is_ai_character')->default(false)->index();
        });

        Schema::create('ai_community_characters', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 32)->unique();
            $table->foreignId('user_id')->nullable()->unique()->constrained('users')->nullOnDelete();
            $table->string('language', 2);
            $table->text('persona_voice');
            $table->text('topics');
            $table->text('prompt_settings')->nullable();
            $table->boolean('enabled')->default(false);
            $table->unsignedInteger('generated_count')->default(0);
            $table->unsignedInteger('published_count')->default(0);
            $table->timestamps();
        });

        Schema::create('ai_community_drafts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('character_id')->constrained('ai_community_characters')->restrictOnDelete();
            $table->text('body');
            $table->char('body_hash', 64)->unique();
            $table->string('status', 16)->default('draft')->index();
            $table->string('post_kind', 48)->nullable()->index();
            $table->string('editorial_angle', 80)->nullable()->index();
            $table->string('style_key', 48)->nullable();
            $table->string('model', 80);
            $table->unsignedInteger('input_tokens')->default(0);
            $table->unsignedInteger('output_tokens')->default(0);
            $table->unsignedBigInteger('estimated_cost_microusd')->default(0);
            $table->string('quality_review_decision', 16)->nullable();
            $table->unsignedTinyInteger('quality_review_naturalness')->nullable();
            $table->unsignedTinyInteger('quality_review_accuracy')->nullable();
            $table->text('quality_review_reason')->nullable();
            $table->timestamp('quality_reviewed_at')->nullable();
            $table->unsignedBigInteger('quality_review_cost_microusd')->default(0);
            $table->unsignedInteger('quality_review_input_tokens')->default(0);
            $table->unsignedInteger('quality_review_output_tokens')->default(0);
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('published_feed_post_id')->nullable()->unique()->constrained('feed_posts')->nullOnDelete();
            $table->timestamps();
            $table->index(['character_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_community_drafts');
        Schema::dropIfExists('ai_community_characters');
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('is_ai_character'));
    }
};
