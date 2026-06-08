<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('feed_posts', function (Blueprint $table): void {
            if (! Schema::hasColumn('feed_posts', 'team_id')) {
                $table->foreignId('team_id')
                    ->nullable()
                    ->after('user_id')
                    ->constrained('teams')
                    ->nullOnDelete();

                $table->index(['team_id', 'created_at']);
            }
        });
    }

    public function down(): void
    {
        Schema::table('feed_posts', function (Blueprint $table): void {
            if (Schema::hasColumn('feed_posts', 'team_id')) {
                $table->dropIndex(['team_id', 'created_at']);
                $table->dropConstrainedForeignId('team_id');
            }
        });
    }
};
