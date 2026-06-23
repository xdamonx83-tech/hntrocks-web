<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('live_lobby_feedback_requests', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('live_lobby_id')->constrained('live_lobbies')->cascadeOnDelete();
            $table->foreignId('reviewer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('target_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('status', 20)->default('pending');
            $table->timestamp('available_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();

            $table->unique(['live_lobby_id', 'reviewer_id', 'target_user_id'], 'live_lobby_feedback_request_unique');
            $table->index(['reviewer_id', 'status', 'available_at'], 'live_lobby_feedback_reviewer_index');
            $table->index('live_lobby_id');
        });

        Schema::create('live_lobby_feedback', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('feedback_request_id')->unique()->constrained('live_lobby_feedback_requests')->cascadeOnDelete();
            $table->foreignId('live_lobby_id')->constrained('live_lobbies')->cascadeOnDelete();
            $table->foreignId('reviewer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('target_user_id')->constrained('users')->cascadeOnDelete();
            $table->json('positive_tags')->nullable();
            $table->json('private_flags')->nullable();
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->index('target_user_id');
            $table->index('reviewer_id');
            $table->index('live_lobby_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('live_lobby_feedback');
        Schema::dropIfExists('live_lobby_feedback_requests');
    }
};
