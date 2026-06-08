<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'two_factor_secret')) {
                $table->text('two_factor_secret')->nullable()->after('remember_token');
            }

            if (! Schema::hasColumn('users', 'two_factor_recovery_codes')) {
                $table->text('two_factor_recovery_codes')->nullable()->after('two_factor_secret');
            }

            if (! Schema::hasColumn('users', 'two_factor_confirmed_at')) {
                $table->timestamp('two_factor_confirmed_at')->nullable()->after('two_factor_recovery_codes');
            }
        });

        if (! Schema::hasTable('user_two_factor_challenges')) {
            Schema::create('user_two_factor_challenges', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('purpose', 48)->index();
                $table->string('token_hash', 64)->unique();
                $table->text('secret')->nullable();
                $table->string('device_name', 120)->nullable();
                $table->string('ip_address', 64)->nullable();
                $table->string('user_agent', 512)->nullable();
                $table->timestamp('expires_at')->index();
                $table->timestamp('used_at')->nullable()->index();
                $table->timestamps();

                $table->index(['user_id', 'purpose', 'expires_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('user_two_factor_challenges');

        Schema::table('users', function (Blueprint $table): void {
            if (Schema::hasColumn('users', 'two_factor_confirmed_at')) {
                $table->dropColumn('two_factor_confirmed_at');
            }

            if (Schema::hasColumn('users', 'two_factor_recovery_codes')) {
                $table->dropColumn('two_factor_recovery_codes');
            }

            if (Schema::hasColumn('users', 'two_factor_secret')) {
                $table->dropColumn('two_factor_secret');
            }
        });
    }
};
