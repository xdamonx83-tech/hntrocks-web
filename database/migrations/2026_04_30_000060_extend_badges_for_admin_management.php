<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('badges', function (Blueprint $table): void {
            if (! Schema::hasColumn('badges', 'icon_path')) {
                $table->string('icon_path')->nullable()->after('icon');
            }

            if (! Schema::hasColumn('badges', 'rarity')) {
                $table->string('rarity', 40)->default('common')->after('category');
            }

            if (! Schema::hasColumn('badges', 'xp_reward')) {
                $table->unsignedInteger('xp_reward')->default(0)->after('description');
            }

            if (! Schema::hasColumn('badges', 'is_manual_only')) {
                $table->boolean('is_manual_only')->default(false)->after('is_active');
            }

            if (! Schema::hasColumn('badges', 'notify_on_award')) {
                $table->boolean('notify_on_award')->default(true)->after('is_manual_only');
            }
        });

        Schema::table('badge_user', function (Blueprint $table): void {
            if (! Schema::hasColumn('badge_user', 'awarded_by')) {
                $table->foreignId('awarded_by')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('badge_user', 'award_reason')) {
                $table->string('award_reason', 500)->nullable()->after('awarded_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('badge_user', function (Blueprint $table): void {
            if (Schema::hasColumn('badge_user', 'awarded_by')) {
                $table->dropForeign(['awarded_by']);
                $table->dropColumn('awarded_by');
            }

            if (Schema::hasColumn('badge_user', 'award_reason')) {
                $table->dropColumn('award_reason');
            }
        });

        Schema::table('badges', function (Blueprint $table): void {
            foreach (['icon_path', 'rarity', 'xp_reward', 'is_manual_only', 'notify_on_award'] as $column) {
                if (Schema::hasColumn('badges', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
