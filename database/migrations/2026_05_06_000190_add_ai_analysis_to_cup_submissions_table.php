<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cup_submissions', function (Blueprint $table): void {
            if (! Schema::hasColumn('cup_submissions', 'screen_type')) {
                $table->string('screen_type', 40)->nullable()->after('review_note');
            }
            if (! Schema::hasColumn('cup_submissions', 'ai_valid_extract')) {
                $table->boolean('ai_valid_extract')->default(false)->after('screen_type');
            }
            if (! Schema::hasColumn('cup_submissions', 'ai_kills')) {
                $table->unsignedInteger('ai_kills')->default(0)->after('ai_valid_extract');
            }
            if (! Schema::hasColumn('cup_submissions', 'ai_bounty_tokens')) {
                $table->unsignedTinyInteger('ai_bounty_tokens')->default(0)->after('ai_kills');
            }
            if (! Schema::hasColumn('cup_submissions', 'ai_confidence')) {
                $table->decimal('ai_confidence', 5, 4)->nullable()->after('ai_bounty_tokens');
            }
            if (! Schema::hasColumn('cup_submissions', 'ai_complete_screenshot')) {
                $table->boolean('ai_complete_screenshot')->nullable()->after('ai_confidence');
            }
            if (! Schema::hasColumn('cup_submissions', 'ai_kills_source')) {
                $table->string('ai_kills_source', 40)->nullable()->after('ai_complete_screenshot');
            }
            if (! Schema::hasColumn('cup_submissions', 'ai_ambiguous_kills')) {
                $table->boolean('ai_ambiguous_kills')->default(false)->after('ai_kills_source');
            }
            if (! Schema::hasColumn('cup_submissions', 'ai_suspected_tampering')) {
                $table->boolean('ai_suspected_tampering')->default(false)->after('ai_ambiguous_kills');
            }
            if (! Schema::hasColumn('cup_submissions', 'ai_invalid_reason')) {
                $table->string('ai_invalid_reason', 80)->nullable()->after('ai_suspected_tampering');
            }
            if (! Schema::hasColumn('cup_submissions', 'ai_raw_result')) {
                $table->json('ai_raw_result')->nullable()->after('ai_invalid_reason');
            }
            if (! Schema::hasColumn('cup_submissions', 'sha256_hash')) {
                $table->string('sha256_hash', 64)->nullable()->after('ai_raw_result');
            }
            if (! Schema::hasColumn('cup_submissions', 'phash')) {
                $table->string('phash', 32)->nullable()->after('sha256_hash');
            }
            if (! Schema::hasColumn('cup_submissions', 'image_width')) {
                $table->unsignedInteger('image_width')->nullable()->after('phash');
            }
            if (! Schema::hasColumn('cup_submissions', 'image_height')) {
                $table->unsignedInteger('image_height')->nullable()->after('image_width');
            }
            if (! Schema::hasColumn('cup_submissions', 'processed_at')) {
                $table->timestamp('processed_at')->nullable()->after('submitted_at');
            }
        });

        Schema::table('cup_submissions', function (Blueprint $table): void {
            $table->index(['cup_id', 'sha256_hash'], 'cup_submissions_cup_sha256_index');
            $table->index(['cup_id', 'phash'], 'cup_submissions_cup_phash_index');
            $table->index(['cup_id', 'ai_invalid_reason'], 'cup_submissions_cup_invalid_reason_index');
        });
    }

    public function down(): void
    {
        Schema::table('cup_submissions', function (Blueprint $table): void {
            $table->dropIndex('cup_submissions_cup_sha256_index');
            $table->dropIndex('cup_submissions_cup_phash_index');
            $table->dropIndex('cup_submissions_cup_invalid_reason_index');
        });

        Schema::table('cup_submissions', function (Blueprint $table): void {
            $table->dropColumn([
                'screen_type',
                'ai_valid_extract',
                'ai_kills',
                'ai_bounty_tokens',
                'ai_confidence',
                'ai_complete_screenshot',
                'ai_kills_source',
                'ai_ambiguous_kills',
                'ai_suspected_tampering',
                'ai_invalid_reason',
                'ai_raw_result',
                'sha256_hash',
                'phash',
                'image_width',
                'image_height',
                'processed_at',
            ]);
        });
    }
};
