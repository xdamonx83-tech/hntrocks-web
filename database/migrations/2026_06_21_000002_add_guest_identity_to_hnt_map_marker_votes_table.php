<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hnt_map_marker_votes', function (Blueprint $table): void {
            $table->string('visitor_hash', 64)->nullable()->after('user_id');
            $table->string('ip_hash', 64)->nullable()->after('visitor_hash');
            $table->string('user_agent_hash', 64)->nullable()->after('ip_hash');

            $table->unique(['hnt_map_marker_id', 'visitor_hash']);
        });
    }

    public function down(): void
    {
        Schema::table('hnt_map_marker_votes', function (Blueprint $table): void {
            $table->dropUnique(['hnt_map_marker_id', 'visitor_hash']);
            $table->dropColumn(['visitor_hash', 'ip_hash', 'user_agent_hash']);
        });
    }
};
