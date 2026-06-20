<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hnt_maps', function (Blueprint $table): void {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->unsignedInteger('width');
            $table->unsignedInteger('height');
            $table->string('image_path')->nullable();
            $table->string('lines_path')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('hnt_map_markers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('hnt_map_id')->constrained('hnt_maps')->cascadeOnDelete();
            $table->string('legacy_key');
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('type')->index();
            $table->decimal('x', 12, 6);
            $table->decimal('y', 12, 6);
            $table->string('label_de')->nullable();
            $table->string('label_en')->nullable();
            $table->string('source_image')->nullable();
            $table->string('status')->default('approved')->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['hnt_map_id', 'legacy_key']);
            $table->index(['hnt_map_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hnt_map_markers');
        Schema::dropIfExists('hnt_maps');
    }
};
