<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('lfg_posts', 'cover_path')) {
            Schema::table('lfg_posts', function (Blueprint $table): void {
                $table->string('cover_path')->nullable()->after('visibility');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('lfg_posts', 'cover_path')) {
            Schema::table('lfg_posts', function (Blueprint $table): void {
                $table->dropColumn('cover_path');
            });
        }
    }
};
