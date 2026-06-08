<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_push_devices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 32)->default('fcm')->index();
            $table->string('platform', 32)->default('android')->index();
            $table->string('device_id')->nullable()->index();
            $table->string('device_name')->nullable();
            $table->string('app_version', 80)->nullable();
            $table->string('locale', 20)->nullable();
            $table->string('timezone', 80)->nullable();
            $table->text('token');
            $table->string('token_hash', 64)->unique();
            $table->timestamp('last_seen_at')->nullable()->index();
            $table->timestamp('disabled_at')->nullable()->index();
            $table->timestamp('revoked_at')->nullable()->index();
            $table->timestamps();

            $table->index(['user_id', 'platform', 'revoked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_push_devices');
    }
};
