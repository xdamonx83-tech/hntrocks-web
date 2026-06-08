<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_assets', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->nullableMorphs('attachable');
            $table->string('context', 60)->default('library')->index();
            $table->string('disk', 40)->default('public');
            $table->string('path');
            $table->string('thumbnail_path')->nullable();
            $table->string('type', 24)->default('file')->index();
            $table->string('mime_type', 120)->nullable();
            $table->string('original_name')->nullable();
            $table->string('extension', 20)->nullable();
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->string('visibility', 24)->default('registered')->index();
            $table->string('status', 24)->default('ready')->index();
            $table->string('alt_text')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'created_at']);
            $table->index(['context', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_assets');
    }
};
