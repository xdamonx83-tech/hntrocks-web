<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hnt_map_marker_votes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('hnt_map_marker_id')->constrained('hnt_map_markers')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->tinyInteger('value');
            $table->timestamps();

            $table->unique(['hnt_map_marker_id', 'user_id']);
            $table->index(['hnt_map_marker_id', 'value']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hnt_map_marker_votes');
    }
};
