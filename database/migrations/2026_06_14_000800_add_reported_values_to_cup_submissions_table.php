<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cup_submissions', function (Blueprint $table): void {
            $table->unsignedTinyInteger('reported_kills')->nullable()->after('extracted');
            $table->unsignedTinyInteger('reported_bounty_tokens')->nullable()->after('reported_kills');
            $table->boolean('reported_extracted')->nullable()->after('reported_bounty_tokens');
        });
    }

    public function down(): void
    {
        Schema::table('cup_submissions', function (Blueprint $table): void {
            $table->dropColumn([
                'reported_kills',
                'reported_bounty_tokens',
                'reported_extracted',
            ]);
        });
    }
};
