<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('feed_posts', function (Blueprint $table): void {
            $table->boolean('is_pinned')->default(false)->index();
            $table->timestamp('pinned_at')->nullable()->index();
            $table->foreignId('pinned_by_user_id')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('feed_posts', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('pinned_by_user_id');
            $table->dropColumn(['is_pinned', 'pinned_at']);
        });
    }
};
