<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('team_progressions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->unique()->constrained('teams')->cascadeOnDelete();
            $table->unsignedInteger('level')->default(1);
            $table->unsignedBigInteger('xp_total')->default(0);
            $table->timestamps();
        });

        Schema::create('team_xp_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event_type', 80)->index();
            $table->string('source_key', 191)->unique();
            $table->unsignedInteger('amount');
            $table->unsignedInteger('level_before');
            $table->unsignedInteger('level_after');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['team_id', 'created_at']);
        });

        Schema::create('team_contract_templates', function (Blueprint $table): void {
            $table->id();
            $table->string('key', 100)->unique();
            $table->string('name_key', 160);
            $table->string('description_key', 160);
            $table->string('category', 60)->index();
            $table->string('metric', 100)->index();
            $table->unsignedInteger('target_value');
            $table->unsignedInteger('minimum_contributors')->default(1);
            $table->unsignedInteger('duration_days')->nullable();
            $table->boolean('is_repeatable')->default(false);
            $table->unsignedInteger('cooldown_days')->nullable();
            $table->unsignedInteger('team_xp_reward')->default(0);
            $table->unsignedInteger('rocks_reward')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->json('configuration')->nullable();
            $table->timestamps();
        });

        Schema::create('team_contracts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('template_id')->constrained('team_contract_templates')->restrictOnDelete();
            $table->foreignId('activated_by')->constrained('users')->restrictOnDelete();
            $table->string('status', 32)->default('active')->index();
            $table->string('name_key', 160);
            $table->string('description_key', 160);
            $table->string('category', 60);
            $table->string('metric', 100);
            $table->unsignedInteger('progress_value')->default(0);
            $table->unsignedInteger('target_value');
            $table->unsignedInteger('minimum_contributors')->default(1);
            $table->unsignedInteger('contributors_count')->default(0);
            $table->unsignedInteger('team_xp_reward');
            $table->unsignedInteger('rocks_reward');
            $table->json('configuration')->nullable();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('rewarded_at')->nullable();
            $table->timestamps();

            $table->index(['team_id', 'status']);
            $table->index(['template_id', 'completed_at']);
            $table->index(['status', 'ends_at']);
        });

        Schema::create('team_contract_contributions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_contract_id')->constrained('team_contracts')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('event_type', 100)->index();
            $table->string('source_key', 191);
            $table->unsignedInteger('amount')->default(1);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['team_contract_id', 'source_key'], 'team_contract_source_unique');
            $table->index(['team_contract_id', 'user_id']);
        });

        Schema::create('team_participations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedBigInteger('points_total')->default(0);
            $table->string('badge_key', 80)->default('member')->index();
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamps();

            $table->unique(['team_id', 'user_id']);
            $table->index(['team_id', 'points_total']);
        });

        Schema::create('team_participation_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('event_type', 100)->index();
            $table->string('source_key', 191);
            $table->unsignedInteger('points');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['team_id', 'source_key']);
            $table->index(['team_id', 'user_id', 'created_at'], 'team_participation_user_date');
        });

        Schema::create('team_sessions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('creator_id')->constrained('users')->restrictOnDelete();
            $table->string('title', 160);
            $table->text('description')->nullable();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at')->nullable();
            $table->string('timezone', 64)->default('UTC');
            $table->string('platform', 60)->nullable();
            $table->string('region', 60)->nullable();
            $table->string('game_mode', 80)->nullable();
            $table->unsignedInteger('max_participants')->nullable();
            $table->boolean('voice_required')->default(false);
            $table->string('status', 32)->default('scheduled')->index();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['team_id', 'starts_at']);
            $table->index(['team_id', 'status']);
        });

        Schema::create('team_session_responses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_session_id')->constrained('team_sessions')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('response', 32)->default('declined')->index();
            $table->boolean('attendance_confirmed')->default(false)->index();
            $table->foreignId('attendance_confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('attendance_confirmed_at')->nullable();
            $table->timestamps();

            $table->unique(['team_session_id', 'user_id']);
            $table->index(['team_session_id', 'response']);
        });

        Schema::create('team_reward_grants', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('team_contract_id')->constrained('team_contracts')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('crown_transaction_id')->nullable()->constrained('crown_transactions')->nullOnDelete();
            $table->string('kind', 64)->default('contract_rocks');
            $table->unsignedInteger('amount');
            $table->timestamp('granted_at')->nullable();
            $table->timestamps();

            $table->unique(['team_contract_id', 'user_id', 'kind'], 'team_reward_user_kind_unique');
            $table->index(['team_id', 'granted_at']);
        });

        $now = now();
        DB::table('teams')->orderBy('id')->chunkById(500, function ($teams) use ($now): void {
            DB::table('team_progressions')->insertOrIgnore($teams->map(fn ($team): array => [
                'team_id' => $team->id,
                'level' => 1,
                'xp_total' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all());
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_reward_grants');
        Schema::dropIfExists('team_session_responses');
        Schema::dropIfExists('team_sessions');
        Schema::dropIfExists('team_participation_events');
        Schema::dropIfExists('team_participations');
        Schema::dropIfExists('team_contract_contributions');
        Schema::dropIfExists('team_contracts');
        Schema::dropIfExists('team_contract_templates');
        Schema::dropIfExists('team_xp_events');
        Schema::dropIfExists('team_progressions');
    }
};
