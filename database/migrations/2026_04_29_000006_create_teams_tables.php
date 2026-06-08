<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teams', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('name', 80);
            $table->string('slug', 120)->unique();
            $table->string('tagline', 140)->nullable();
            $table->text('description')->nullable();
            $table->string('platform', 40)->nullable()->index();
            $table->string('playstyle', 60)->nullable()->index();
            $table->string('region', 60)->nullable()->index();
            $table->string('language', 40)->nullable()->index();
            $table->string('visibility', 24)->default('public')->index();
            $table->string('recruitment_status', 24)->default('open')->index();
            $table->string('avatar_path')->nullable();
            $table->string('cover_path')->nullable();
            $table->string('status', 24)->default('active')->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['owner_id', 'created_at']);
        });

        Schema::create('team_members', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role', 24)->default('member')->index();
            $table->string('status', 24)->default('pending')->index();
            $table->text('message')->nullable();
            $table->foreignId('accepted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('joined_at')->nullable();
            $table->timestamps();

            $table->unique(['team_id', 'user_id']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_members');
        Schema::dropIfExists('teams');
    }
};
