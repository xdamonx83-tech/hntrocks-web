<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crown_transactions', function (Blueprint $table): void {
            if (! Schema::hasColumn('crown_transactions', 'claim_dismissed_at')) {
                $table->timestamp('claim_dismissed_at')->nullable()->after('collected_at')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('crown_transactions', function (Blueprint $table): void {
            if (Schema::hasColumn('crown_transactions', 'claim_dismissed_at')) {
                $table->dropColumn('claim_dismissed_at');
            }
        });
    }
};
