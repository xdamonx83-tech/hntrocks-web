<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('hnt_map_cash_spot_submissions', 'announcement_feed_post_id')) {
            Schema::table('hnt_map_cash_spot_submissions', function (Blueprint $table): void {
                $table->foreignId('announcement_feed_post_id')
                    ->nullable()
                    ->unique('cash_spot_announcement_post_unique')
                    ->constrained('feed_posts')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('hnt_map_cash_spot_submissions', 'announcement_feed_post_id')) {
            Schema::table('hnt_map_cash_spot_submissions', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('announcement_feed_post_id');
            });
        }
    }
};
