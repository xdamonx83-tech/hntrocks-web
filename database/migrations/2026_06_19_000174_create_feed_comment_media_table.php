<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feed_comment_media', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('feed_comment_id')->constrained('feed_comments')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('media_asset_id')->nullable()->constrained('media_assets')->nullOnDelete();
            $table->string('disk', 40)->default('public');
            $table->string('path');
            $table->string('mime_type', 120)->nullable();
            $table->string('original_name')->nullable();
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['feed_comment_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feed_comment_media');
    }
};
