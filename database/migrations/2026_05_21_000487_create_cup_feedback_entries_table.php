<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cup_feedback_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('cup_id')->nullable()->constrained('cups')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('category', 40)->default('improvement')->index();
            $table->string('subject', 160)->nullable();
            $table->text('message')->nullable();
            $table->unsignedTinyInteger('rating_overall')->nullable();
            $table->unsignedTinyInteger('rating_rules')->nullable();
            $table->unsignedTinyInteger('rating_scoring')->nullable();
            $table->unsignedTinyInteger('rating_submission')->nullable();
            $table->unsignedTinyInteger('rating_fairness')->nullable();
            $table->string('would_join_again', 20)->nullable()->index();
            $table->string('preferred_next_format', 40)->nullable()->index();
            $table->json('liked_options')->nullable();
            $table->json('issue_options')->nullable();
            $table->json('idea_options')->nullable();
            $table->boolean('contact_allowed')->default(true);
            $table->string('status', 24)->default('new')->index();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('admin_note')->nullable();
            $table->timestamp('submitted_at')->nullable()->index();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['cup_id', 'status']);
            $table->index(['user_id', 'submitted_at']);
            $table->index(['category', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cup_feedback_entries');
    }
};
