<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hnt_map_cash_spot_submissions', function (Blueprint $table): void {
            $table->text('description')->nullable()->after('size');
        });
    }

    public function down(): void
    {
        Schema::table('hnt_map_cash_spot_submissions', function (Blueprint $table): void {
            $table->dropColumn('description');
        });
    }
};
