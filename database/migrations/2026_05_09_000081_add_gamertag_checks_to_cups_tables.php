<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cup_teams', function (Blueprint $table): void {
            if (! Schema::hasColumn('cup_teams', 'detected_gamertag')) {
                $table->string('detected_gamertag', 100)->nullable()->after('last_submission_at');
            }
            if (! Schema::hasColumn('cup_teams', 'detected_gamertag_normalized')) {
                $table->string('detected_gamertag_normalized', 100)->nullable()->after('detected_gamertag');
            }
            if (! Schema::hasColumn('cup_teams', 'detected_gamertag_confidence')) {
                $table->decimal('detected_gamertag_confidence', 5, 4)->nullable()->after('detected_gamertag_normalized');
            }
            if (! Schema::hasColumn('cup_teams', 'detected_gamertag_locked_at')) {
                $table->timestamp('detected_gamertag_locked_at')->nullable()->after('detected_gamertag_confidence');
            }
        });

        Schema::table('cup_submissions', function (Blueprint $table): void {
            if (! Schema::hasColumn('cup_submissions', 'ai_gamertag')) {
                $table->string('ai_gamertag', 100)->nullable()->after('ai_suspected_tampering');
            }
            if (! Schema::hasColumn('cup_submissions', 'ai_gamertag_normalized')) {
                $table->string('ai_gamertag_normalized', 100)->nullable()->after('ai_gamertag');
            }
            if (! Schema::hasColumn('cup_submissions', 'ai_gamertag_confidence')) {
                $table->decimal('ai_gamertag_confidence', 5, 4)->nullable()->after('ai_gamertag_normalized');
            }
            if (! Schema::hasColumn('cup_submissions', 'ai_gamertag_mismatch')) {
                $table->boolean('ai_gamertag_mismatch')->default(false)->after('ai_gamertag_confidence');
            }
        });
    }

    public function down(): void
    {
        Schema::table('cup_submissions', function (Blueprint $table): void {
            $columns = [];
            foreach (['ai_gamertag', 'ai_gamertag_normalized', 'ai_gamertag_confidence', 'ai_gamertag_mismatch'] as $column) {
                if (Schema::hasColumn('cup_submissions', $column)) {
                    $columns[] = $column;
                }
            }
            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });

        Schema::table('cup_teams', function (Blueprint $table): void {
            $columns = [];
            foreach (['detected_gamertag', 'detected_gamertag_normalized', 'detected_gamertag_confidence', 'detected_gamertag_locked_at'] as $column) {
                if (Schema::hasColumn('cup_teams', $column)) {
                    $columns[] = $column;
                }
            }
            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
