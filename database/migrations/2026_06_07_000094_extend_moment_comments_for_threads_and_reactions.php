<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('moment_comments', function (Blueprint $table): void {
            if (! Schema::hasColumn('moment_comments', 'parent_id')) {
                $table->foreignId('parent_id')
                    ->nullable()
                    ->after('user_id')
                    ->constrained('moment_comments')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('moment_comments', 'likes_count')) {
                $table->unsignedInteger('likes_count')->default(0)->after('body');
            }

            if (! Schema::hasColumn('moment_comments', 'replies_count')) {
                $table->unsignedInteger('replies_count')->default(0)->after('likes_count');
            }
        });

        Schema::create('moment_comment_reactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('moment_comment_id')->constrained('moment_comments')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 30)->default('like');
            $table->timestamps();

            $table->unique(['moment_comment_id', 'user_id', 'type'], 'moment_comment_reactions_unique');
            $table->index(['user_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('moment_comment_reactions');

        Schema::table('moment_comments', function (Blueprint $table): void {
            if (Schema::hasColumn('moment_comments', 'parent_id')) {
                $table->dropConstrainedForeignId('parent_id');
            }

            if (Schema::hasColumn('moment_comments', 'replies_count')) {
                $table->dropColumn('replies_count');
            }

            if (Schema::hasColumn('moment_comments', 'likes_count')) {
                $table->dropColumn('likes_count');
            }
        });
    }
};
