<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crown_transactions', function (Blueprint $table): void {
            if (! Schema::hasColumn('crown_transactions', 'collected_at')) {
                $table->timestamp('collected_at')->nullable()->after('metadata')->index();
            }
        });

        if (Schema::hasColumn('crown_transactions', 'collected_at')) {
            DB::table('crown_transactions')
                ->whereNull('collected_at')
                ->update(['collected_at' => DB::raw('created_at')]);
        }
    }

    public function down(): void
    {
        Schema::table('crown_transactions', function (Blueprint $table): void {
            if (Schema::hasColumn('crown_transactions', 'collected_at')) {
                $table->dropColumn('collected_at');
            }
        });
    }
};
