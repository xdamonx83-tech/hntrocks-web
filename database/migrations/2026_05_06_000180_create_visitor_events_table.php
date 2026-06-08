<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('visitor_events')) {
            return;
        }

        Schema::create('visitor_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('visitor_hash', 64)->index();
            $table->string('session_hash', 64)->nullable()->index();
            $table->string('ip_hash', 64)->nullable()->index();
            $table->string('country_code', 2)->nullable()->index();
            $table->string('country_name', 100)->nullable();
            $table->string('source', 100)->nullable()->index();
            $table->string('referrer_host')->nullable();
            $table->string('path')->index();
            $table->string('route_name')->nullable()->index();
            $table->string('user_agent_hash', 64)->nullable();
            $table->boolean('is_first_visit')->default(false)->index();
            $table->timestamp('occurred_at')->index();
            $table->timestamps();

            $table->index(['occurred_at', 'country_code']);
            $table->index(['occurred_at', 'source']);
            $table->index(['visitor_hash', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visitor_events');
    }
};
