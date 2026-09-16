<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cup_submissions', function (Blueprint $table): void {
            $table->unsignedTinyInteger('banishes')->default(0)->after('bounty_tokens');
        });
    }

    public function down(): void
    {
        Schema::table('cup_submissions', function (Blueprint $table): void {
            $table->dropColumn('banishes');
        });
    }
};
