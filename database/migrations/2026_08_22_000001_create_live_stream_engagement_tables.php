<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('live_stream_comments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('streamer_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->char('stream_key', 64);
            $table->string('body', 500);
            $table->timestamps();

            $table->index(['streamer_user_id', 'stream_key', 'id'], 'live_comments_stream_index');
        });

        Schema::create('live_stream_heart_totals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('streamer_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->char('stream_key', 64);
            $table->unsignedBigInteger('count')->default(0);
            $table->timestamps();

            $table->unique(
                ['streamer_user_id', 'user_id', 'stream_key'],
                'live_hearts_viewer_stream_unique'
            );
            $table->index(['streamer_user_id', 'stream_key'], 'live_hearts_stream_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('live_stream_heart_totals');
        Schema::dropIfExists('live_stream_comments');
    }
};
