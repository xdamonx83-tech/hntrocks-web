<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quests', function (Blueprint $table): void {
            if (! Schema::hasColumn('quests', 'icon')) {
                $table->string('icon', 40)->nullable()->after('badge_slug');
            }

            if (! Schema::hasColumn('quests', 'icon_path')) {
                $table->string('icon_path')->nullable()->after('icon');
            }

            if (! Schema::hasColumn('quests', 'notify_on_completion')) {
                $table->boolean('notify_on_completion')->default(true)->after('is_active');
            }
        });
    }

    public function down(): void
    {
        Schema::table('quests', function (Blueprint $table): void {
            foreach (['icon', 'icon_path', 'notify_on_completion'] as $column) {
                if (Schema::hasColumn('quests', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
