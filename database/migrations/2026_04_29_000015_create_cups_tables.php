<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cups', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('title', 140);
            $table->string('slug', 170)->unique();
            $table->string('summary', 255)->nullable();
            $table->text('rules')->nullable();
            $table->string('platform', 40)->nullable()->index();
            $table->string('region', 60)->nullable()->index();
            $table->string('language', 60)->nullable()->index();
            $table->unsignedTinyInteger('team_size')->default(3);
            $table->unsignedInteger('max_teams')->nullable();
            $table->string('status', 40)->default('planned')->index();
            $table->string('visibility', 40)->default('public')->index();
            $table->timestamp('starts_at')->nullable()->index();
            $table->timestamp('ends_at')->nullable()->index();
            $table->timestamp('registration_opens_at')->nullable();
            $table->timestamp('registration_closes_at')->nullable();
            $table->string('cover_path')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'visibility', 'starts_at']);
        });

        Schema::create('cup_teams', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('cup_id')->constrained('cups')->cascadeOnDelete();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('join_token', 64)->unique();
            $table->string('status', 40)->default('active')->index();
            $table->unsignedInteger('points_total')->default(0);
            $table->unsignedInteger('kills_total')->default(0);
            $table->unsignedInteger('bounty_tokens_total')->default(0);
            $table->unsignedInteger('submissions_approved_count')->default(0);
            $table->timestamp('last_submission_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['cup_id', 'name']);
            $table->index(['cup_id', 'points_total']);
        });

        Schema::create('cup_team_members', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('cup_team_id')->constrained('cup_teams')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role', 40)->default('member')->index();
            $table->string('status', 40)->default('active')->index();
            $table->timestamp('joined_at')->nullable();
            $table->timestamps();

            $table->unique(['cup_team_id', 'user_id']);
            $table->index(['user_id', 'status']);
        });

        Schema::create('cup_submissions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('cup_id')->constrained('cups')->cascadeOnDelete();
            $table->foreignId('cup_team_id')->constrained('cup_teams')->cascadeOnDelete();
            $table->foreignId('submitted_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('screenshot_media_asset_id')->nullable()->constrained('media_assets')->nullOnDelete();
            $table->unsignedInteger('kills')->default(0);
            $table->unsignedTinyInteger('bounty_tokens')->default(0);
            $table->boolean('extracted')->default(false);
            $table->unsignedInteger('points')->default(0);
            $table->string('status', 40)->default('pending')->index();
            $table->text('note')->nullable();
            $table->text('review_note')->nullable();
            $table->timestamp('submitted_at')->nullable()->index();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['cup_id', 'status']);
            $table->index(['cup_team_id', 'submitted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cup_submissions');
        Schema::dropIfExists('cup_team_members');
        Schema::dropIfExists('cup_teams');
        Schema::dropIfExists('cups');
    }
};
