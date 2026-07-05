<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_remote_configs', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->boolean('is_active')->default(false)->index();
            $table->json('config_json')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('app_remote_feed_cards', function (Blueprint $table): void {
            $table->id();
            $table->string('remote_id')->unique();
            $table->string('title_de');
            $table->string('title_en')->nullable();
            $table->text('body_de');
            $table->text('body_en')->nullable();
            $table->string('cta_label_de')->nullable();
            $table->string('cta_label_en')->nullable();
            $table->string('action_url')->nullable();
            $table->string('style_variant')->default('gold_glass');
            $table->integer('priority')->default(100)->index();
            $table->boolean('is_active')->default(false)->index();
            $table->boolean('dismissible')->default(true);
            $table->timestamp('starts_at')->nullable()->index();
            $table->timestamp('ends_at')->nullable()->index();
            $table->string('audience_type')->default('all')->index();
            $table->json('audience_payload')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('app_remote_feed_card_dismissals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('remote_card_id')->nullable()->constrained('app_remote_feed_cards')->nullOnDelete();
            $table->string('remote_id');
            $table->timestamp('dismissed_at');
            $table->timestamps();

            $table->unique(['user_id', 'remote_id']);
        });

        Schema::create('app_push_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('admin_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('target_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->text('body');
            $table->string('action_url')->nullable();
            $table->json('data_json')->nullable();
            $table->json('result_json')->nullable();
            $table->unsignedInteger('sent_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_push_logs');
        Schema::dropIfExists('app_remote_feed_card_dismissals');
        Schema::dropIfExists('app_remote_feed_cards');
        Schema::dropIfExists('app_remote_configs');
    }
};
