<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('cup_teams') && ! Schema::hasColumn('cup_teams', 'is_recruiting')) {
            Schema::table('cup_teams', function (Blueprint $table): void {
                $table->boolean('is_recruiting')->default(false)->after('roster_locked_at')->index();
            });
        }

        if (! Schema::hasTable('cup_team_finder_posts')) {
            Schema::create('cup_team_finder_posts', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('cup_id')->constrained('cups')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('platform', 40)->nullable()->index();
                $table->string('status', 40)->default('active')->index();
                $table->text('message')->nullable();
                $table->timestamp('closed_at')->nullable();
                $table->timestamps();

                $table->unique(['cup_id', 'user_id']);
                $table->index(['cup_id', 'status', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('cup_team_finder_posts');

        if (Schema::hasTable('cup_teams') && Schema::hasColumn('cup_teams', 'is_recruiting')) {
            Schema::table('cup_teams', function (Blueprint $table): void {
                $table->dropColumn('is_recruiting');
            });
        }
    }
};
