<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('approved_outbound_links')) {
            Schema::create('approved_outbound_links', function (Blueprint $table): void {
                $table->id();
                $table->string('title', 160);
                $table->string('slug', 140)->unique();
                $table->text('description')->nullable();
                $table->string('target_url', 2048);
                $table->string('target_domain', 190)->nullable()->index();
                $table->unsignedBigInteger('clicks_count')->default(0);
                $table->boolean('is_active')->default(true)->index();
                $table->timestamp('last_clicked_at')->nullable()->index();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->text('admin_note')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('approved_outbound_links');
    }
};
