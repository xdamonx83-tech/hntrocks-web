<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hnt_map_marker_uploads', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('hnt_map_marker_id')->constrained('hnt_map_markers')->cascadeOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('disk')->default('local');
            $table->string('path');
            $table->string('public_path')->nullable();
            $table->string('original_name');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size');
            $table->string('status')->default('pending')->index();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['hnt_map_marker_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hnt_map_marker_uploads');
    }
};
