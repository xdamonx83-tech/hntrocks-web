<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guides', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('title', 160);
            $table->string('slug', 190)->unique();
            $table->string('summary', 320)->nullable();
            $table->string('cover_path')->nullable();
            $table->string('category', 80)->index();
            $table->json('tags')->nullable();
            $table->string('language', 10)->default('de')->index();
            $table->string('difficulty', 24)->default('beginner')->index();
            $table->string('platform', 24)->default('all')->index();
            $table->string('status', 32)->default('draft')->index();
            $table->boolean('is_featured')->default(false)->index();
            $table->unsignedSmallInteger('reading_time_minutes')->default(1);
            $table->unsignedInteger('views_count')->default(0);
            $table->unsignedInteger('helpful_count')->default(0);
            $table->unsignedInteger('comments_count')->default(0);
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'published_at']);
            $table->index(['category', 'status']);
            $table->index(['language', 'platform', 'difficulty']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guides');
    }
};
