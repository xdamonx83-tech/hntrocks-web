<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cup_ideas', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('cup_id')->nullable()->constrained('cups')->nullOnDelete();
            $table->string('title', 160);
            $table->string('category', 40)->default('other')->index();
            $table->text('description');
            $table->string('status', 32)->default('new')->index();
            $table->boolean('is_featured')->default(false)->index();
            $table->unsignedInteger('votes_count')->default(0)->index();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('admin_note')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['category', 'status']);
            $table->index(['is_featured', 'votes_count']);
            $table->index(['created_at', 'votes_count']);
        });

        Schema::create('cup_idea_votes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('cup_idea_id')->constrained('cup_ideas')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['cup_idea_id', 'user_id']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cup_idea_votes');
        Schema::dropIfExists('cup_ideas');
    }
};
