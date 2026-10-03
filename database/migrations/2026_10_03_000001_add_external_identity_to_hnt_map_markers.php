<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hnt_map_markers', function (Blueprint $table): void {
            $table->string('source_provider', 64)->nullable()->after('source_id');
            $table->string('source_key', 120)->nullable()->after('source_provider');
            $table->string('source_category', 80)->nullable()->after('source_key');
            $table->string('subtype', 80)->nullable()->after('type');
            $table->char('source_payload_hash', 64)->nullable()->after('subtype');
            $table->timestamp('last_synced_at')->nullable()->after('source_payload_hash');
            $table->unique(['hnt_map_id', 'source_provider', 'source_key'], 'hnt_marker_external_identity_unique');
        });
    }

    public function down(): void
    {
        Schema::table('hnt_map_markers', function (Blueprint $table): void {
            $table->dropUnique('hnt_marker_external_identity_unique');
            $table->dropColumn(['source_provider', 'source_key', 'source_category', 'subtype', 'source_payload_hash', 'last_synced_at']);
        });
    }
};
