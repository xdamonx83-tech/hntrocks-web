<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_notifications', function (Blueprint $table): void {
            $table->json('data')->nullable();
            $table->string('dedupe_key')->nullable()->unique();
        });
    }

    public function down(): void
    {
        Schema::table('user_notifications', function (Blueprint $table): void {
            $table->dropUnique(['dedupe_key']);
            $table->dropColumn(['data', 'dedupe_key']);
        });
    }
};
