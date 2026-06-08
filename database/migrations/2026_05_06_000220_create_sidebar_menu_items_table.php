<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sidebar_menu_items', function (Blueprint $table): void {
            $table->id();
            $table->string('menu_key')->unique();
            $table->string('label')->nullable();
            $table->string('label_key')->nullable();
            $table->string('route_name')->nullable();
            $table->string('url', 2048)->nullable();
            $table->string('match_pattern')->nullable();
            $table->string('phosphor_icon')->default('circle');
            $table->string('section')->default('main');
            $table->unsignedInteger('sort_order')->default(100);
            $table->boolean('is_enabled')->default(true);
            $table->boolean('admin_only')->default(false);
            $table->boolean('is_custom')->default(false);
            $table->timestamps();

            $table->index(['is_enabled', 'admin_only', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sidebar_menu_items');
    }
};
