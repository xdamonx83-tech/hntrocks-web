<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('moment_spotlights', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('moment_id')->constrained('moments')->cascadeOnDelete();
            $table->foreignId('selected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title', 160)->nullable();
            $table->text('note')->nullable();
            $table->date('week_starts_at')->nullable()->index();
            $table->date('week_ends_at')->nullable()->index();
            $table->string('status', 40)->default('active')->index();
            $table->boolean('is_active')->default(false)->index();
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamps();

            $table->index(['is_active', 'status', 'week_starts_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('moment_spotlights');
    }
};
