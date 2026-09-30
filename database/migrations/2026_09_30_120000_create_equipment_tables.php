<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('equipment_sources', function (Blueprint $t) {
            $t->id(); $t->string('key')->unique(); $t->string('name'); $t->string('base_url')->nullable(); $t->text('license_note')->nullable(); $t->timestamps();
        });
        Schema::create('equipment_families', function (Blueprint $t) {
            $t->id(); $t->string('key')->unique(); $t->string('name'); $t->timestamps();
        });
        Schema::create('equipment_items', function (Blueprint $t) {
            $t->id(); $t->foreignId('source_id')->constrained('equipment_sources'); $t->string('external_id');
            $t->string('slug')->unique(); $t->string('name'); $t->string('item_type', 32); $t->string('category')->nullable();
            $t->string('equipment_class')->nullable(); $t->string('comparison_group')->index();
            $t->foreignId('family_id')->nullable()->constrained('equipment_families');
            $t->string('ammo_type')->nullable(); $t->unsignedSmallInteger('slot_size')->nullable();
            $t->unsignedInteger('price')->nullable(); $t->unsignedSmallInteger('unlock_rank')->nullable();
            $t->string('source_status', 16)->default('active')->index();
            $t->string('source_url')->nullable(); $t->string('source_hash', 64)->nullable();
            $t->string('original_asset_url')->nullable(); $t->string('local_asset_path')->nullable(); $t->text('license_note')->nullable();
            $t->timestamp('source_updated_at')->nullable(); $t->timestamp('last_synced_at')->nullable();
            $t->json('facts')->nullable(); $t->timestamps(); $t->unique(['source_id', 'external_id']);
        });
        Schema::create('equipment_translations', function (Blueprint $t) {
            $t->id(); $t->foreignId('equipment_item_id')->constrained('equipment_items')->cascadeOnDelete();
            $t->string('locale', 2); $t->string('name')->nullable(); $t->text('description')->nullable();
            $t->boolean('description_is_manual')->default(false); $t->timestamps(); $t->unique(['equipment_item_id', 'locale']);
        });
        Schema::create('equipment_stat_definitions', function (Blueprint $t) {
            $t->id(); $t->string('key')->unique(); $t->string('label'); $t->string('unit')->nullable();
            $t->string('comparison_direction', 8); $t->string('group'); $t->unsignedSmallInteger('sort_order'); $t->timestamps();
        });
        Schema::create('equipment_stats', function (Blueprint $t) {
            $t->id(); $t->foreignId('equipment_item_id')->constrained('equipment_items')->cascadeOnDelete();
            $t->foreignId('stat_definition_id')->constrained('equipment_stat_definitions'); $t->decimal('value', 12, 3);
            $t->unique(['equipment_item_id', 'stat_definition_id']);
        });
        Schema::create('equipment_ammo', function (Blueprint $t) {
            $t->id(); $t->foreignId('equipment_item_id')->constrained('equipment_items')->cascadeOnDelete();
            $t->string('key'); $t->string('name'); $t->string('ammo_type')->nullable();
            $t->decimal('damage', 10, 2)->nullable(); $t->decimal('velocity', 10, 2)->nullable();
            $t->unsignedSmallInteger('loaded')->nullable(); $t->unsignedSmallInteger('reserve')->nullable();
            $t->json('facts')->nullable(); $t->unique(['equipment_item_id', 'key']);
        });
        Schema::create('equipment_falloff_points', function (Blueprint $t) {
            $t->id(); $t->foreignId('equipment_ammo_id')->constrained('equipment_ammo')->cascadeOnDelete();
            $t->decimal('distance', 10, 2); $t->decimal('damage', 10, 2); $t->unique(['equipment_ammo_id', 'distance']);
        });
        Schema::create('equipment_traits', function (Blueprint $t) {
            $t->id(); $t->string('external_id')->unique(); $t->string('name');
        });
        Schema::create('equipment_item_traits', function (Blueprint $t) {
            $t->foreignId('equipment_item_id')->constrained('equipment_items')->cascadeOnDelete();
            $t->foreignId('equipment_trait_id')->constrained('equipment_traits')->cascadeOnDelete();
            $t->primary(['equipment_item_id', 'equipment_trait_id']);
        });
        Schema::create('equipment_skins', function (Blueprint $t) {
            $t->id(); $t->foreignId('equipment_item_id')->constrained('equipment_items')->cascadeOnDelete();
            $t->string('external_id'); $t->string('name')->nullable(); $t->string('rarity')->nullable();
            $t->string('source_url')->nullable(); $t->string('original_asset_url')->nullable();
            $t->string('local_asset_path')->nullable(); $t->text('license_note')->nullable();
            $t->unique(['equipment_item_id', 'external_id']);
        });
        Schema::create('equipment_patch_history', function (Blueprint $t) {
            $t->id(); $t->foreignId('equipment_item_id')->constrained('equipment_items')->cascadeOnDelete();
            $t->string('patch'); $t->string('field')->nullable(); $t->string('old_value')->nullable(); $t->string('new_value')->nullable();
            $t->string('source_url')->nullable(); $t->text('note')->nullable();
        });
        Schema::create('equipment_sync_runs', function (Blueprint $t) {
            $t->id(); $t->foreignId('source_id')->constrained('equipment_sources'); $t->string('status');
            $t->boolean('dry_run')->default(false); $t->json('counts'); $t->timestamp('started_at'); $t->timestamp('finished_at')->nullable();
            $t->timestamps();
        });
        Schema::create('equipment_sync_changes', function (Blueprint $t) {
            $t->id(); $t->foreignId('sync_run_id')->constrained('equipment_sync_runs')->cascadeOnDelete();
            $t->foreignId('equipment_item_id')->nullable()->constrained('equipment_items')->nullOnDelete();
            $t->string('external_id'); $t->string('change_type'); $t->string('field')->nullable();
            $t->json('old_value')->nullable(); $t->json('new_value')->nullable(); $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['equipment_sync_changes','equipment_sync_runs','equipment_patch_history','equipment_skins','equipment_item_traits','equipment_traits','equipment_falloff_points','equipment_ammo','equipment_stats','equipment_stat_definitions','equipment_translations','equipment_items','equipment_families','equipment_sources'] as $table) Schema::dropIfExists($table);
    }
};
