<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_loadouts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title', 80);
            $table->string('primary_weapon', 80)->nullable();
            $table->string('secondary_weapon', 80)->nullable();
            $table->json('tools')->nullable();
            $table->json('consumables')->nullable();
            $table->json('traits')->nullable();
            $table->text('note')->nullable();
            $table->string('visibility', 20)->default('public');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['user_id', 'is_active', 'visibility', 'sort_order'], 'user_loadouts_profile_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_loadouts');
    }
};
