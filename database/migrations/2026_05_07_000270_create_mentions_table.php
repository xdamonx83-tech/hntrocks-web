<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mentions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('mentioner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('mentioned_user_id')->constrained('users')->cascadeOnDelete();
            $table->morphs('mentionable');
            $table->string('context', 40)->default('feed')->index();
            $table->timestamps();

            $table->unique(['mentionable_type', 'mentionable_id', 'mentioned_user_id'], 'mentions_subject_user_unique');
            $table->index(['mentioned_user_id', 'context', 'created_at']);
            $table->index(['mentioner_id', 'context', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mentions');
    }
};
