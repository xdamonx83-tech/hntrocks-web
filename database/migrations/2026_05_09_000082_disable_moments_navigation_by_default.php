<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sidebar_menu_items')) {
            DB::table('sidebar_menu_items')
                ->where('menu_key', 'moments')
                ->update(['is_enabled' => false, 'updated_at' => now()]);
        }

        if (Schema::hasTable('mobile_nav_items')) {
            DB::table('mobile_nav_items')
                ->where('menu_key', 'moments')
                ->update(['is_enabled' => false, 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('sidebar_menu_items')) {
            DB::table('sidebar_menu_items')
                ->where('menu_key', 'moments')
                ->update(['is_enabled' => true, 'updated_at' => now()]);
        }

        if (Schema::hasTable('mobile_nav_items')) {
            DB::table('mobile_nav_items')
                ->where('menu_key', 'moments')
                ->update(['is_enabled' => true, 'updated_at' => now()]);
        }
    }
};
