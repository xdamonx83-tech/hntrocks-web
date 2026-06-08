<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cup_randomizer_draws', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('cup_id')->constrained('cups')->cascadeOnDelete();
            $table->foreignId('cup_team_id')->nullable()->constrained('cup_teams')->nullOnDelete();
            $table->foreignId('drawn_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title', 140)->nullable();
            $table->string('prize_label', 180)->nullable();
            $table->json('eligible_team_ids')->nullable();
            $table->unsignedInteger('eligible_team_count')->default(0);
            $table->json('team_snapshot')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['cup_id', 'created_at']);
            $table->index(['cup_id', 'cup_team_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cup_randomizer_draws');
    }
};
