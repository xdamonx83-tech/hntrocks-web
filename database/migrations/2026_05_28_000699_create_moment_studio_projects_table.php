<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('moment_studio_projects', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('moment_id')->nullable()->constrained('moments')->nullOnDelete();
            $table->string('status', 40)->default('draft')->index();
            $table->string('visibility', 40)->default('public')->index();
            $table->string('caption', 220)->nullable();
            $table->text('description')->nullable();
            $table->json('timeline')->nullable();
            $table->json('source_media_asset_ids')->nullable();
            $table->foreignId('output_media_asset_id')->nullable()->constrained('media_assets')->nullOnDelete();
            $table->unsignedInteger('total_duration_seconds')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('moment_studio_projects');
    }
};
