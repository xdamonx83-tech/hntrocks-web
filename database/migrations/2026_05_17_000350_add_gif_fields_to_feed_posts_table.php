<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('feed_posts', function (Blueprint $table): void {
            $table->string('gif_provider', 40)->nullable()->after('feeling_key');
            $table->string('gif_id')->nullable()->after('gif_provider');
            $table->text('gif_url')->nullable()->after('gif_id');
            $table->text('gif_preview_url')->nullable()->after('gif_url');
            $table->string('gif_title')->nullable()->after('gif_preview_url');
            $table->text('gif_source_url')->nullable()->after('gif_title');
        });
    }

    public function down(): void
    {
        Schema::table('feed_posts', function (Blueprint $table): void {
            $table->dropColumn([
                'gif_provider',
                'gif_id',
                'gif_url',
                'gif_preview_url',
                'gif_title',
                'gif_source_url',
            ]);
        });
    }
};
