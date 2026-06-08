<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cup_teams', function (Blueprint $table): void {
            if (! Schema::hasColumn('cup_teams', 'roster_locked_at')) {
                $table->timestamp('roster_locked_at')->nullable()->after('last_submission_at')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('cup_teams', function (Blueprint $table): void {
            if (Schema::hasColumn('cup_teams', 'roster_locked_at')) {
                $table->dropColumn('roster_locked_at');
            }
        });
    }
};
