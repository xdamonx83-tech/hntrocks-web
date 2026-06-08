<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('feed_posts', function (Blueprint $table): void {
            if (! Schema::hasColumn('feed_posts', 'cup_team_id')) {
                $table->foreignId('cup_team_id')
                    ->nullable()
                    ->after('team_id')
                    ->constrained('cup_teams')
                    ->nullOnDelete();

                $table->index(['cup_team_id', 'created_at'], 'feed_posts_cup_team_created_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('feed_posts', function (Blueprint $table): void {
            if (Schema::hasColumn('feed_posts', 'cup_team_id')) {
                $table->dropIndex('feed_posts_cup_team_created_idx');
                $table->dropConstrainedForeignId('cup_team_id');
            }
        });
    }
};
