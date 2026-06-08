<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('feed_posts', function (Blueprint $table): void {
            if (! Schema::hasColumn('feed_posts', 'shared_post_id')) {
                $table->foreignId('shared_post_id')
                    ->nullable()
                    ->after('team_id')
                    ->constrained('feed_posts')
                    ->nullOnDelete();

                $table->index(['shared_post_id', 'created_at']);
            }
        });
    }

    public function down(): void
    {
        Schema::table('feed_posts', function (Blueprint $table): void {
            if (Schema::hasColumn('feed_posts', 'shared_post_id')) {
                $table->dropIndex(['shared_post_id', 'created_at']);
                $table->dropConstrainedForeignId('shared_post_id');
            }
        });
    }
};
