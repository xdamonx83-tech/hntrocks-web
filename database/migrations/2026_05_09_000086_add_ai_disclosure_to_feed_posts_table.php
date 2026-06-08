<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('feed_posts', function (Blueprint $table): void {
            if (! Schema::hasColumn('feed_posts', 'ai_user_declared')) {
                $table->boolean('ai_user_declared')->default(false)->after('source_language');
            }

            if (! Schema::hasColumn('feed_posts', 'ai_detected_possible')) {
                $table->boolean('ai_detected_possible')->default(false)->after('ai_user_declared');
            }

            if (! Schema::hasColumn('feed_posts', 'ai_detection_confidence')) {
                $table->decimal('ai_detection_confidence', 5, 4)->nullable()->after('ai_detected_possible');
            }

            if (! Schema::hasColumn('feed_posts', 'ai_detection_reason')) {
                $table->text('ai_detection_reason')->nullable()->after('ai_detection_confidence');
            }

            if (! Schema::hasColumn('feed_posts', 'ai_detection_source')) {
                $table->string('ai_detection_source', 60)->nullable()->after('ai_detection_reason');
            }

            if (! Schema::hasColumn('feed_posts', 'ai_detection_model')) {
                $table->string('ai_detection_model', 120)->nullable()->after('ai_detection_source');
            }

            if (! Schema::hasColumn('feed_posts', 'ai_detection_error')) {
                $table->string('ai_detection_error', 120)->nullable()->after('ai_detection_model');
            }

            if (! Schema::hasColumn('feed_posts', 'ai_detection_checked_at')) {
                $table->timestamp('ai_detection_checked_at')->nullable()->after('ai_detection_error');
            }

            if (! Schema::hasColumn('feed_posts', 'admin_confirmed_ai')) {
                $table->boolean('admin_confirmed_ai')->default(false)->after('ai_detection_checked_at');
            }

            if (! Schema::hasColumn('feed_posts', 'admin_ai_reviewed_at')) {
                $table->timestamp('admin_ai_reviewed_at')->nullable()->after('admin_confirmed_ai');
            }

            if (! Schema::hasColumn('feed_posts', 'admin_ai_reviewed_by_user_id')) {
                $table->foreignId('admin_ai_reviewed_by_user_id')
                    ->nullable()
                    ->after('admin_ai_reviewed_at')
                    ->constrained('users')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('feed_posts', function (Blueprint $table): void {
            if (Schema::hasColumn('feed_posts', 'admin_ai_reviewed_by_user_id')) {
                $table->dropConstrainedForeignId('admin_ai_reviewed_by_user_id');
            }

            foreach ([
                'admin_ai_reviewed_at',
                'admin_confirmed_ai',
                'ai_detection_checked_at',
                'ai_detection_error',
                'ai_detection_model',
                'ai_detection_source',
                'ai_detection_reason',
                'ai_detection_confidence',
                'ai_detected_possible',
                'ai_user_declared',
            ] as $column) {
                if (Schema::hasColumn('feed_posts', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
