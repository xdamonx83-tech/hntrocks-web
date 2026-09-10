<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('app_push_logs', function (Blueprint $table): void {
            $table->dropForeign(['target_user_id']);
            $table->foreignId('target_user_id')->nullable()->change();
            $table->foreign('target_user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        if (DB::table('app_push_logs')->whereNull('target_user_id')->exists()) {
            throw new RuntimeException('Cannot make app_push_logs.target_user_id required while broadcast logs exist.');
        }

        Schema::table('app_push_logs', function (Blueprint $table): void {
            $table->dropForeign(['target_user_id']);
            $table->foreignId('target_user_id')->nullable(false)->change();
            $table->foreign('target_user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};
