<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('live_lobby_members', function (Blueprint $table): void {
            $table->unsignedTinyInteger('hunter_number')->nullable()->after('mmr_stars');
        });
    }

    public function down(): void
    {
        Schema::table('live_lobby_members', function (Blueprint $table): void {
            $table->dropColumn('hunter_number');
        });
    }
};
