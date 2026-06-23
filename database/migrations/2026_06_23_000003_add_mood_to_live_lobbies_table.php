<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('live_lobbies', function (Blueprint $table): void {
            $table->string('mood', 40)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('live_lobbies', function (Blueprint $table): void {
            $table->dropColumn('mood');
        });
    }
};
