<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referral_links', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('code', 32)->unique();
            $table->unsignedInteger('clicks_count')->default(0);
            $table->unsignedInteger('signups_count')->default(0);
            $table->unsignedInteger('completed_profiles_count')->default(0);
            $table->timestamp('last_clicked_at')->nullable();
            $table->timestamps();
        });

        Schema::create('referral_signups', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('referral_link_id')->nullable()->constrained('referral_links')->nullOnDelete();
            $table->foreignId('referrer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('referred_user_id')->nullable()->unique()->constrained('users')->nullOnDelete();
            $table->string('referral_code', 32)->index();
            $table->timestamp('registered_at')->nullable();
            $table->timestamp('profile_completed_at')->nullable();
            $table->string('status', 40)->default('registered');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['referrer_id', 'status']);
        });

        Schema::create('giveaways', function (Blueprint $table): void {
            $table->id();
            $table->string('slug', 120)->unique();
            $table->string('title', 180);
            $table->text('description')->nullable();
            $table->string('status', 40)->default('draft');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->unsignedTinyInteger('profile_completion_required')->default(100);
            $table->unsignedTinyInteger('base_entries')->default(1);
            $table->unsignedTinyInteger('profile_bonus_entries')->default(1);
            $table->unsignedTinyInteger('referral_bonus_entries')->default(1);
            $table->unsignedTinyInteger('max_referral_bonus_entries')->default(1);
            $table->json('rules')->nullable();
            $table->timestamps();

            $table->index(['status', 'starts_at', 'ends_at']);
        });

        Schema::create('giveaway_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('giveaway_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('source', 80);
            $table->unsignedTinyInteger('entries')->default(1);
            $table->string('reason', 255)->nullable();
            $table->nullableMorphs('source');
            $table->timestamp('awarded_at')->useCurrent();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['giveaway_id', 'user_id', 'source', 'source_type', 'source_id'], 'giveaway_entries_unique_source');
            $table->index(['giveaway_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('giveaway_entries');
        Schema::dropIfExists('giveaways');
        Schema::dropIfExists('referral_signups');
        Schema::dropIfExists('referral_links');
    }
};
