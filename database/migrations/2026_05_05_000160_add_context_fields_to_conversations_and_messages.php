<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table): void {
            if (! Schema::hasColumn('conversations', 'context_type')) {
                $table->string('context_type')->nullable()->after('title')->index();
            }

            if (! Schema::hasColumn('conversations', 'context_id')) {
                $table->unsignedBigInteger('context_id')->nullable()->after('context_type')->index();
            }

            if (! Schema::hasColumn('conversations', 'context_label')) {
                $table->string('context_label')->nullable()->after('context_id');
            }

            if (! Schema::hasColumn('conversations', 'context_url')) {
                $table->string('context_url')->nullable()->after('context_label');
            }
        });

        Schema::table('messages', function (Blueprint $table): void {
            if (! Schema::hasColumn('messages', 'type')) {
                $table->string('type')->default('user')->after('user_id')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table): void {
            if (Schema::hasColumn('messages', 'type')) {
                $table->dropColumn('type');
            }
        });

        Schema::table('conversations', function (Blueprint $table): void {
            foreach (['context_url', 'context_label', 'context_id', 'context_type'] as $column) {
                if (Schema::hasColumn('conversations', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
