<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversation_participants', function (Blueprint $table): void {
            if (! Schema::hasColumn('conversation_participants', 'cleared_at')) {
                $table->timestamp('cleared_at')->nullable()->after('last_read_at');
                $table->index(['user_id', 'cleared_at']);
            }
        });
    }

    public function down(): void
    {
        Schema::table('conversation_participants', function (Blueprint $table): void {
            if (Schema::hasColumn('conversation_participants', 'cleared_at')) {
                $table->dropIndex(['user_id', 'cleared_at']);
                $table->dropColumn('cleared_at');
            }
        });
    }
};
