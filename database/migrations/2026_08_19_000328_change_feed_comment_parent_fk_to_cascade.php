<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('feed_comments', function (Blueprint $table): void {
            $table->dropForeign('feed_comments_parent_id_foreign');
            $table->foreign('parent_id', 'feed_comments_parent_id_foreign')
                ->references('id')
                ->on('feed_comments')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('feed_comments', function (Blueprint $table): void {
            $table->dropForeign('feed_comments_parent_id_foreign');
            $table->foreign('parent_id', 'feed_comments_parent_id_foreign')
                ->references('id')
                ->on('feed_comments')
                ->nullOnDelete();
        });
    }
};
