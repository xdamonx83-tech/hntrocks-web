<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cup_teams', function (Blueprint $table): void {
            if (! Schema::hasColumn('cup_teams', 'disqualified_at')) {
                $table->timestamp('disqualified_at')->nullable()->after('status');
            }

            if (! Schema::hasColumn('cup_teams', 'disqualified_by')) {
                $table->foreignId('disqualified_by')
                    ->nullable()
                    ->after('disqualified_at')
                    ->constrained('users')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('cup_teams', 'disqualification_reason')) {
                $table->text('disqualification_reason')->nullable()->after('disqualified_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('cup_teams', function (Blueprint $table): void {
            if (Schema::hasColumn('cup_teams', 'disqualified_by')) {
                $table->dropConstrainedForeignId('disqualified_by');
            }

            if (Schema::hasColumn('cup_teams', 'disqualification_reason')) {
                $table->dropColumn('disqualification_reason');
            }

            if (Schema::hasColumn('cup_teams', 'disqualified_at')) {
                $table->dropColumn('disqualified_at');
            }
        });
    }
};
