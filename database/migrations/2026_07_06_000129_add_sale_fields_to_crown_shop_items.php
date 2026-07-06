<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('crown_shop_items')) {
            return;
        }

        Schema::table('crown_shop_items', function (Blueprint $table): void {
            if (! Schema::hasColumn('crown_shop_items', 'sale_price')) {
                $table->unsignedInteger('sale_price')->nullable()->after('price')->index();
            }

            if (! Schema::hasColumn('crown_shop_items', 'offer_starts_at')) {
                $table->timestamp('offer_starts_at')->nullable()->after('available_until')->index();
            }

            if (! Schema::hasColumn('crown_shop_items', 'offer_ends_at')) {
                $table->timestamp('offer_ends_at')->nullable()->after('offer_starts_at')->index();
            }

            if (! Schema::hasColumn('crown_shop_items', 'badge')) {
                $table->string('badge', 40)->nullable()->after('offer_ends_at')->index();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('crown_shop_items')) {
            return;
        }

        $columns = collect([
            'badge',
            'offer_ends_at',
            'offer_starts_at',
            'sale_price',
        ])->filter(fn (string $column): bool => Schema::hasColumn('crown_shop_items', $column))->values()->all();

        if ($columns === []) {
            return;
        }

        Schema::table('crown_shop_items', function (Blueprint $table) use ($columns): void {
            $table->dropColumn($columns);
        });
    }
};
