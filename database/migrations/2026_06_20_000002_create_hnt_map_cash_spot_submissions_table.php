<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hnt_map_cash_spot_submissions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('hnt_map_id')->constrained('hnt_maps')->cascadeOnDelete();
            $table->foreignId('hnt_map_marker_id')->nullable()->constrained('hnt_map_markers')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('x', 12, 6);
            $table->decimal('y', 12, 6);
            $table->string('status')->default('pending');
            $table->string('disk')->default('local');
            $table->string('path');
            $table->string('public_path')->nullable();
            $table->string('original_name');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size');
            $table->string('submitter_name', 80)->nullable();
            $table->string('submitter_email', 160)->nullable();
            $table->string('ip_hash', 64)->nullable();
            $table->string('user_agent_hash', 64)->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->index(['hnt_map_id', 'status']);
            $table->index(['status', 'created_at']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hnt_map_cash_spot_submissions');
    }
};
