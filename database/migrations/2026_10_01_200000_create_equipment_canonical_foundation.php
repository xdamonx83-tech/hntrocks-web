<?php

use App\Services\Equipment\EquipmentStatCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('equipment_source_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipment_item_id')->nullable()->constrained('equipment_items')->nullOnDelete();
            $table->foreignId('equipment_source_id')->nullable()->constrained('equipment_sources')->nullOnDelete();
            $table->string('source_key', 80);
            $table->string('source_external_id')->nullable();
            $table->string('source_revision_id')->nullable();
            $table->timestamp('source_revision_timestamp')->nullable();
            $table->string('source_url')->nullable();
            $table->char('payload_hash', 64);
            $table->char('identity_hash', 64)->unique();
            $table->json('normalized_payload');
            $table->timestamp('fetched_at');
            $table->timestamps();
            $table->index(['equipment_item_id', 'source_key', 'fetched_at'], 'equipment_snapshot_item_source_time');
            $table->index(['source_key', 'source_external_id'], 'equipment_snapshot_source_external');
        });

        Schema::create('equipment_field_provenance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipment_item_id')->constrained('equipment_items')->cascadeOnDelete();
            $table->string('field_key');
            $table->string('source_key', 80);
            $table->foreignId('source_snapshot_id')->nullable()->constrained('equipment_source_snapshots')->nullOnDelete();
            $table->string('source_revision_id')->nullable();
            $table->boolean('is_manual_override')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['equipment_item_id', 'field_key'], 'equipment_provenance_item_field');
            $table->index(['source_key', 'source_revision_id']);
        });

        Schema::create('equipment_family_aliases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipment_family_id')->constrained('equipment_families')->cascadeOnDelete();
            $table->string('source_key', 80);
            $table->string('alias');
            $table->string('alias_key');
            $table->string('canonical_name')->nullable();
            $table->timestamps();
            $table->unique(['source_key', 'alias_key'], 'equipment_family_alias_source_key');
        });

        $defined = collect(EquipmentStatCatalog::DEFINITIONS)->keyBy(fn (array $row) => $row[0]);
        foreach (['dropRange' => 65, 'effectDuration' => 195, 'swapSpeed' => 105, 'throwStamina' => 145] as $key => $sortOrder) {
            [$statKey, $label, $unit, $direction, $group] = $defined[$key];
            DB::table('equipment_stat_definitions')->insertOrIgnore([
                'key' => $statKey, 'label' => $label, 'unit' => $unit,
                'comparison_direction' => $direction, 'group' => $group,
                'sort_order' => $sortOrder,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment_family_aliases');
        Schema::dropIfExists('equipment_field_provenance');
        Schema::dropIfExists('equipment_source_snapshots');
    }
};
