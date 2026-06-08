<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lfg_posts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('title', 120);
            $table->text('body')->nullable();
            $table->string('platform', 40)->nullable()->index();
            $table->string('playstyle', 60)->nullable()->index();
            $table->string('region', 60)->nullable()->index();
            $table->string('language', 40)->nullable()->index();
            $table->string('preferred_time', 80)->nullable()->index();
            $table->string('experience_level', 60)->nullable()->index();
            $table->boolean('voice_required')->default(false)->index();
            $table->unsignedTinyInteger('slots_total')->default(2);
            $table->unsignedTinyInteger('slots_filled')->default(1);
            $table->string('status', 24)->default('open')->index();
            $table->string('visibility', 24)->default('public')->index();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status', 'created_at']);
        });

        Schema::create('lfg_applications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('lfg_post_id')->constrained('lfg_posts')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('message')->nullable();
            $table->string('status', 24)->default('pending')->index();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();

            $table->unique(['lfg_post_id', 'user_id']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lfg_applications');
        Schema::dropIfExists('lfg_posts');
    }
};
