<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('team_lfg_posts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->nullable()->constrained('teams')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('type', 40)->default('team_seeks_players')->index();
            $table->string('title', 140);
            $table->text('body')->nullable();
            $table->string('platform', 40)->nullable()->index();
            $table->string('playstyle', 60)->nullable()->index();
            $table->string('region', 60)->nullable()->index();
            $table->string('language', 40)->nullable()->index();
            $table->string('preferred_time', 80)->nullable();
            $table->string('experience_level', 60)->nullable();
            $table->boolean('voice_required')->default(false)->index();
            $table->unsignedSmallInteger('slots_total')->nullable();
            $table->unsignedSmallInteger('slots_filled')->default(0);
            $table->string('status', 24)->default('open')->index();
            $table->string('visibility', 24)->default('public')->index();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['team_id', 'status']);
            $table->index(['user_id', 'created_at']);
            $table->index(['type', 'status', 'created_at']);
        });

        Schema::create('team_lfg_applications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_lfg_post_id')->constrained('team_lfg_posts')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('team_id')->nullable()->constrained('teams')->cascadeOnDelete();
            $table->text('message')->nullable();
            $table->string('status', 24)->default('pending')->index();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();

            $table->unique(['team_lfg_post_id', 'user_id']);
            $table->index(['team_id', 'status']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_lfg_applications');
        Schema::dropIfExists('team_lfg_posts');
    }
};
