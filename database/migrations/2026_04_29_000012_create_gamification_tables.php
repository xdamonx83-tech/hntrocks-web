<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->unsignedInteger('xp_total')->default(0)->after('cover_path');
            $table->unsignedSmallInteger('level')->default(1)->after('xp_total');
            $table->unsignedInteger('trust_score')->default(0)->after('level');
            $table->timestamp('last_xp_at')->nullable()->after('trust_score');
        });

        Schema::create('xp_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('action', 80);
            $table->integer('points');
            $table->nullableMorphs('source');
            $table->string('description', 255)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'action']);
        });

        Schema::create('badges', function (Blueprint $table): void {
            $table->id();
            $table->string('slug', 80)->unique();
            $table->string('name', 120);
            $table->string('category', 80)->default('community');
            $table->string('icon', 40)->nullable();
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('badge_user', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('badge_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('awarded_at')->useCurrent();
            $table->timestamps();

            $table->unique(['badge_id', 'user_id']);
        });

        Schema::create('quests', function (Blueprint $table): void {
            $table->id();
            $table->string('slug', 100)->unique();
            $table->string('name', 140);
            $table->string('category', 80)->default('daily');
            $table->string('action', 80);
            $table->unsignedInteger('target_count')->default(1);
            $table->unsignedInteger('xp_reward')->default(0);
            $table->string('badge_slug', 80)->nullable();
            $table->text('description')->nullable();
            $table->string('period', 40)->nullable();
            $table->boolean('is_repeatable')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('quest_user', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('quest_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('progress_count')->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('reward_claimed_at')->nullable();
            $table->timestamps();

            $table->unique(['quest_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quest_user');
        Schema::dropIfExists('quests');
        Schema::dropIfExists('badge_user');
        Schema::dropIfExists('badges');
        Schema::dropIfExists('xp_events');

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['xp_total', 'level', 'trust_score', 'last_xp_at']);
        });
    }
};
