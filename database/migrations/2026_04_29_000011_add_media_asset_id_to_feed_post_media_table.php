<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('feed_post_media', function (Blueprint $table): void {
            $table->foreignId('media_asset_id')
                ->nullable()
                ->after('user_id')
                ->constrained('media_assets')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('feed_post_media', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('media_asset_id');
        });
    }
};
