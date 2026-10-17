<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->unsignedInteger('prestige')->default(0)->after('level');
            $table->unsignedInteger('xp_cycle')->default(0)->after('prestige');
            $table->boolean('prestige_xp_boost')->default(false)->after('xp_cycle');
            $table->unsignedTinyInteger('prestige_xp_bonus_remainder')->default(0)->after('prestige_xp_boost');
        });

        // Preserve existing lifetime XP; cap only the current cycle (Level 50).
        // CASE works with both SQLite in tests and the production MySQL database.
        DB::table('users')->update([
            'xp_cycle' => DB::raw('CASE WHEN xp_total >= 12250 THEN 12250 ELSE xp_total END'),
            'level' => DB::raw('CASE WHEN level > 50 THEN 50 ELSE level END'),
        ]);

        Schema::create('hunter_prestige_history', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('prestige_number');
            $table->string('reward_type', 20);
            $table->foreignId('shop_item_id')->nullable()->constrained('crown_shop_items')->nullOnDelete();
            $table->unsignedInteger('career_xp_at_prestige');
            $table->unsignedInteger('cycle_xp_at_prestige');
            $table->timestamps();

            $table->unique(['user_id', 'prestige_number'], 'hunter_prestige_unique_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hunter_prestige_history');

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn([
                'prestige', 'xp_cycle', 'prestige_xp_boost', 'prestige_xp_bonus_remainder',
            ]);
        });
    }
};
