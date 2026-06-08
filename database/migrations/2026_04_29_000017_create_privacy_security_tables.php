<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_privacy_settings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('profile_visibility', 24)->default('public')->index();
            $table->string('allow_messages_from', 24)->default('registered')->index();
            $table->boolean('allow_team_invites')->default(true);
            $table->boolean('allow_lfg_invites')->default(true);
            $table->boolean('show_online_status')->default(true);
            $table->boolean('show_activity_feed')->default(true);
            $table->boolean('show_gamification')->default(true);
            $table->boolean('data_usage_consent')->default(false);
            $table->timestamps();
        });

        Schema::create('user_blocks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('blocked_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('reason', 120)->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'blocked_user_id']);
            $table->index(['blocked_user_id', 'created_at']);
        });

        Schema::create('user_security_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event', 64)->index();
            $table->string('ip_address', 64)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['event', 'created_at']);
        });

        Schema::create('account_deletion_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('status', 24)->default('pending')->index();
            $table->text('reason')->nullable();
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('scheduled_for')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });

        if (! Schema::hasColumn('users', 'last_login_at')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->timestamp('last_login_at')->nullable()->after('suspended_at');
            });
        }

        if (! Schema::hasColumn('users', 'last_login_ip')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->string('last_login_ip', 64)->nullable()->after('last_login_at');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('account_deletion_requests');
        Schema::dropIfExists('user_security_events');
        Schema::dropIfExists('user_blocks');
        Schema::dropIfExists('user_privacy_settings');

        Schema::table('users', function (Blueprint $table): void {
            if (Schema::hasColumn('users', 'last_login_ip')) {
                $table->dropColumn('last_login_ip');
            }

            if (Schema::hasColumn('users', 'last_login_at')) {
                $table->dropColumn('last_login_at');
            }
        });
    }
};
