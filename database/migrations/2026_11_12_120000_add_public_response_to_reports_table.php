<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table): void {
            // Public text entered explicitly by moderators. Never show
            // the separate resolution_note, which is staff-only.
            $table->text('public_response')->nullable()->after('resolution_note');
        });
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table): void {
            $table->dropColumn('public_response');
        });
    }
};
