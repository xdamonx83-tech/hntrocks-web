<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guide_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('slug', 80)->unique();
            $table->string('name_de', 120);
            $table->string('name_en', 120);
            $table->string('description_de', 255)->nullable();
            $table->string('description_en', 255)->nullable();
            $table->string('icon', 60)->default('ph-book-open');
            $table->unsignedSmallInteger('sort_order')->default(0)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('guides', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('author_id')->constrained('users')->restrictOnDelete();
            $table->string('slug', 160)->unique();
            $table->string('status', 32)->default('draft')->index();
            $table->unsignedBigInteger('current_published_revision_id')->nullable()->index();
            $table->unsignedBigInteger('working_revision_id')->nullable()->index();
            $table->boolean('is_featured')->default(false)->index();
            $table->unsignedInteger('helpful_count')->default(0);
            $table->unsignedInteger('bookmarks_count')->default(0);
            $table->unsignedInteger('comments_count')->default(0);
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamp('archived_at')->nullable()->index();
            $table->timestamps();
            $table->index(['status', 'archived_at', 'published_at'], 'guides_public_listing_idx');
            $table->index(['author_id', 'status', 'updated_at'], 'guides_author_status_idx');
        });

        Schema::create('guide_revisions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('guide_id')->constrained('guides')->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->foreignId('author_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('guide_categories')->restrictOnDelete();
            $table->unsignedBigInteger('cover_media_id')->nullable()->index();
            $table->string('title', 160)->default('');
            $table->string('summary', 420)->default('');
            $table->json('tags')->nullable();
            $table->string('language', 8)->default('de')->index();
            $table->string('difficulty', 24)->default('beginner')->index();
            $table->string('platform', 24)->default('all')->index();
            $table->json('content_blocks')->nullable();
            $table->unsignedSmallInteger('reading_time_minutes')->default(1);
            $table->string('status', 32)->default('draft')->index();
            $table->timestamp('submitted_at')->nullable()->index();
            $table->timestamp('reviewed_at')->nullable()->index();
            $table->foreignId('moderator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('moderation_reason')->nullable();
            $table->timestamps();
            $table->unique(['guide_id', 'version']);
            $table->index(['status', 'submitted_at'], 'guide_revisions_queue_idx');
            $table->index(['category_id', 'language', 'status'], 'guide_revisions_filters_idx');
        });

        Schema::table('guides', function (Blueprint $table): void {
            $table->foreign('current_published_revision_id', 'guides_published_revision_fk')
                ->references('id')->on('guide_revisions')->nullOnDelete();
            $table->foreign('working_revision_id', 'guides_working_revision_fk')
                ->references('id')->on('guide_revisions')->nullOnDelete();
        });

        Schema::create('guide_media', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('guide_id')->constrained('guides')->cascadeOnDelete();
            $table->foreignId('revision_id')->nullable()->constrained('guide_revisions')->cascadeOnDelete();
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('media_asset_id')->nullable()->unique()->constrained('media_assets')->nullOnDelete();
            $table->string('kind', 24)->default('content')->index();
            $table->string('disk', 32)->default('local');
            $table->string('path', 255)->unique();
            $table->string('original_name', 255);
            $table->string('mime_type', 80);
            $table->unsignedBigInteger('size_bytes');
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->timestamp('orphaned_at')->nullable()->index();
            $table->timestamps();
            $table->index(['guide_id', 'revision_id', 'kind'], 'guide_media_owner_idx');
        });

        Schema::table('guide_revisions', function (Blueprint $table): void {
            $table->foreign('cover_media_id', 'guide_revisions_cover_media_fk')
                ->references('id')->on('guide_media')->nullOnDelete();
        });

        Schema::create('guide_moderation_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('guide_id')->constrained('guides')->cascadeOnDelete();
            $table->foreignId('revision_id')->nullable()->constrained('guide_revisions')->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 40)->index();
            $table->string('from_status', 32)->nullable();
            $table->string('to_status', 32)->nullable();
            $table->text('reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['guide_id', 'created_at']);
        });

        Schema::create('guide_comments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('guide_id')->constrained('guides')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('guide_comments')->nullOnDelete();
            $table->text('body');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['guide_id', 'parent_id', 'created_at'], 'guide_comments_thread_idx');
        });

        Schema::create('guide_helpful_votes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('guide_id')->constrained('guides')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['guide_id', 'user_id']);
        });

        Schema::create('guide_bookmarks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('guide_id')->constrained('guides')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['guide_id', 'user_id']);
            $table->index(['user_id', 'created_at']);
        });

        Schema::create('guide_reputation_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('guide_id')->nullable()->constrained('guides')->cascadeOnDelete();
            $table->string('event_type', 48)->index();
            $table->string('event_key', 190)->unique();
            $table->smallInteger('points');
            $table->string('description', 255)->nullable();
            $table->timestamp('reversed_at')->nullable()->index();
            $table->timestamps();
            $table->index(['user_id', 'reversed_at'], 'guide_reputation_total_idx');
        });

        $now = now();
        DB::table('guide_categories')->insert([
            ['slug' => 'beginner', 'name_de' => 'Anfänger', 'name_en' => 'Beginner', 'description_de' => 'Grundlagen und erste Schritte', 'description_en' => 'Basics and first steps', 'icon' => 'ph-compass', 'sort_order' => 10, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['slug' => 'weapons-loadouts', 'name_de' => 'Waffen und Loadouts', 'name_en' => 'Weapons and Loadouts', 'description_de' => 'Ausrüstung, Traits und Builds', 'description_en' => 'Equipment, traits and builds', 'icon' => 'ph-crosshair', 'sort_order' => 20, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['slug' => 'hunters-traits', 'name_de' => 'Hunter und Traits', 'name_en' => 'Hunters and Traits', 'description_de' => 'Hunter, Traits und Synergien', 'description_en' => 'Hunters, traits and synergies', 'icon' => 'ph-person-simple-run', 'sort_order' => 30, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['slug' => 'maps', 'name_de' => 'Karten', 'name_en' => 'Maps', 'description_de' => 'Gebiete, Spots und Routen', 'description_en' => 'Areas, spots and routes', 'icon' => 'ph-map-trifold', 'sort_order' => 40, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['slug' => 'bosses-pve', 'name_de' => 'Bosse und PvE', 'name_en' => 'Bosses and PvE', 'description_de' => 'Bosse, Monster und Umgebung', 'description_en' => 'Bosses, monsters and environment', 'icon' => 'ph-skull', 'sort_order' => 50, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['slug' => 'pvp-tactics', 'name_de' => 'PvP und Taktik', 'name_en' => 'PvP and Tactics', 'description_de' => 'Kämpfe, Positionierung und Entscheidungen', 'description_en' => 'Combat, positioning and decisions', 'icon' => 'ph-strategy', 'sort_order' => 60, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['slug' => 'teams', 'name_de' => 'Teams', 'name_en' => 'Teams', 'description_de' => 'Kommunikation und Teamplay', 'description_en' => 'Communication and team play', 'icon' => 'ph-users-three', 'sort_order' => 70, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['slug' => 'events', 'name_de' => 'Events', 'name_en' => 'Events', 'description_de' => 'Events und zeitlich begrenzte Inhalte', 'description_en' => 'Events and limited-time content', 'icon' => 'ph-calendar-star', 'sort_order' => 80, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('guide_reputation_entries');
        Schema::dropIfExists('guide_bookmarks');
        Schema::dropIfExists('guide_helpful_votes');
        Schema::dropIfExists('guide_comments');
        Schema::dropIfExists('guide_moderation_events');

        Schema::table('guide_revisions', function (Blueprint $table): void {
            $table->dropForeign('guide_revisions_cover_media_fk');
        });
        Schema::dropIfExists('guide_media');

        Schema::table('guides', function (Blueprint $table): void {
            $table->dropForeign('guides_published_revision_fk');
            $table->dropForeign('guides_working_revision_fk');
        });
        Schema::dropIfExists('guide_revisions');
        Schema::dropIfExists('guides');
        Schema::dropIfExists('guide_categories');
    }
};
