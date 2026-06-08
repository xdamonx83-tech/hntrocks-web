<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quests', function (Blueprint $table): void {
            if (! Schema::hasColumn('quests', 'is_weekly_contract')) {
                $table->boolean('is_weekly_contract')->default(false)->after('period');
            }

            if (! Schema::hasColumn('quests', 'contract_starts_at')) {
                $table->timestamp('contract_starts_at')->nullable()->after('is_weekly_contract');
            }

            if (! Schema::hasColumn('quests', 'contract_ends_at')) {
                $table->timestamp('contract_ends_at')->nullable()->after('contract_starts_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('quests', function (Blueprint $table): void {
            foreach (['contract_ends_at', 'contract_starts_at', 'is_weekly_contract'] as $column) {
                if (Schema::hasColumn('quests', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
