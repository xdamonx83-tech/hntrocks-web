<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'is_admin')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->boolean('is_admin')->default(false)->after('remember_token')->index();
            });
        }

        if (! Schema::hasColumn('users', 'status')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->string('status', 24)->default('active')->after('is_admin')->index();
            });
        }

        if (! Schema::hasColumn('users', 'suspended_at')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->timestamp('suspended_at')->nullable()->after('status');
            });
        }

        Schema::create('reports', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('reporter_id')->nullable()->constrained('users')->nullOnDelete();
            $table->nullableMorphs('reportable');
            $table->string('category', 40)->default('content')->index();
            $table->string('reason', 80)->default('other')->index();
            $table->text('body')->nullable();
            $table->string('status', 24)->default('open')->index();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('resolution_note')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['category', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');

        Schema::table('users', function (Blueprint $table): void {
            if (Schema::hasColumn('users', 'suspended_at')) {
                $table->dropColumn('suspended_at');
            }

            if (Schema::hasColumn('users', 'status')) {
                $table->dropColumn('status');
            }

            if (Schema::hasColumn('users', 'is_admin')) {
                $table->dropColumn('is_admin');
            }
        });
    }
};
