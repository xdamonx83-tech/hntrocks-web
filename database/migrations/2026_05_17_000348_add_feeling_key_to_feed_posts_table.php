<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('feed_posts', function (Blueprint $table): void {
            if (! Schema::hasColumn('feed_posts', 'feeling_key')) {
                $table->string('feeling_key', 40)->nullable()->after('background_style');
            }
        });
    }

    public function down(): void
    {
        Schema::table('feed_posts', function (Blueprint $table): void {
            if (Schema::hasColumn('feed_posts', 'feeling_key')) {
                $table->dropColumn('feeling_key');
            }
        });
    }
};
