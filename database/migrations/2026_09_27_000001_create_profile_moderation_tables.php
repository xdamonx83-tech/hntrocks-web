<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profile_moderation_flags', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_profile_id')->constrained('user_profiles')->cascadeOnDelete();
            $table->string('field', 32);
            $table->string('category', 64);
            $table->unsignedTinyInteger('score')->default(0);
            $table->string('reason', 255);
            $table->text('excerpt')->nullable();
            $table->string('fingerprint', 64)->unique();
            $table->string('source', 24)->default('automatic');
            $table->string('status', 24)->default('pending');
            $table->timestamp('detected_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('admin_note')->nullable();
            $table->timestamps();

            $table->index(['status', 'score']);
            $table->index(['user_id', 'status']);
            $table->index(['field', 'status']);
        });

        Schema::create('profile_moderation_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 64);
            $table->string('field', 32)->nullable();
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->string('reason', 120)->nullable();
            $table->text('note')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'created_at']);
            $table->index(['action', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profile_moderation_events');
        Schema::dropIfExists('profile_moderation_flags');
    }
};
