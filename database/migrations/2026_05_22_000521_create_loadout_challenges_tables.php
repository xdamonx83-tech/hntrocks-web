<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loadout_challenges', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title', 160);
            $table->string('slug', 180)->unique();
            $table->string('summary', 240)->nullable();
            $table->text('description')->nullable();
            $table->text('rules')->nullable();
            $table->text('loadout_notes')->nullable();
            $table->string('status', 32)->default('draft')->index();
            $table->timestamp('starts_at')->nullable()->index();
            $table->timestamp('ends_at')->nullable()->index();
            $table->unsignedInteger('xp_reward')->default(0);
            $table->string('badge_slug', 80)->nullable()->index();
            $table->boolean('is_featured')->default(false)->index();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'starts_at', 'ends_at']);
            $table->index(['is_featured', 'sort_order']);
        });

        Schema::create('loadout_challenge_submissions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('loadout_challenge_id')->constrained('loadout_challenges')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('media_asset_id')->nullable()->constrained('media_assets')->nullOnDelete();
            $table->string('outcome', 80)->nullable();
            $table->text('body')->nullable();
            $table->string('status', 32)->default('pending')->index();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('xp_awarded_at')->nullable();
            $table->text('admin_note')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['loadout_challenge_id', 'status']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loadout_challenge_submissions');
        Schema::dropIfExists('loadout_challenges');
    }
};
