<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('feed_posts', function (Blueprint $table): void {
            if (! Schema::hasColumn('feed_posts', 'background_style')) {
                $table->string('background_style', 40)->nullable()->after('body');
            }
        });
    }

    public function down(): void
    {
        Schema::table('feed_posts', function (Blueprint $table): void {
            if (Schema::hasColumn('feed_posts', 'background_style')) {
                $table->dropColumn('background_style');
            }
        });
    }
};
