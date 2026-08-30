<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('arcade_games', function (Blueprint $table): void {
            $table->id();
            $table->string('key', 100)->unique();
            $table->string('name_de');
            $table->string('name_en');
            $table->text('description_de')->nullable();
            $table->text('description_en')->nullable();
            $table->string('type', 30);
            $table->string('status', 30)->default('disabled');
            $table->string('cover_path')->nullable();
            $table->integer('sort_order')->default(0);
            $table->unsignedSmallInteger('min_players')->default(1);
            $table->unsignedSmallInteger('max_players')->default(1);
            $table->boolean('casual_enabled')->default(false);
            $table->boolean('ranked_enabled')->default(false);
            $table->string('client_engine_key', 100)->nullable();
            $table->string('min_client_version', 50)->nullable();
            $table->unsignedInteger('game_version')->default(1);
            $table->string('launch_url', 2048)->nullable();
            $table->string('badge_de')->nullable();
            $table->string('badge_en')->nullable();
            $table->json('settings')->nullable();
            $table->json('reward_settings')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'sort_order']);
            $table->index(['type', 'status']);
            $table->index(['published_at', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('arcade_games');
    }
};
