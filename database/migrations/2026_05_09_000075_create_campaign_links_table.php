<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('campaign_links')) {
            Schema::create('campaign_links', function (Blueprint $table): void {
                $table->id();
                $table->string('slug')->unique();
                $table->string('label');
                $table->string('target_url');
                $table->string('utm_source', 80)->nullable();
                $table->string('utm_medium', 80)->nullable();
                $table->string('utm_campaign', 120)->nullable();
                $table->unsignedBigInteger('clicks_count')->default(0);
                $table->boolean('is_active')->default(true)->index();
                $table->timestamp('last_clicked_at')->nullable()->index();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('campaign_link_clicks')) {
            Schema::create('campaign_link_clicks', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('campaign_link_id')->constrained('campaign_links')->cascadeOnDelete();
                $table->timestamp('occurred_at')->index();
                $table->timestamps();

                $table->index(['campaign_link_id', 'occurred_at']);
            });
        }

        $now = now();
        $campaignExists = DB::table('campaign_links')
            ->where('slug', 'bayou-blood-cup')
            ->exists();

        if ($campaignExists) {
            DB::table('campaign_links')
                ->where('slug', 'bayou-blood-cup')
                ->update([
                    'label' => 'Bayou Blood Cup Launch',
                    'target_url' => '/cups/bayou-blood-cup',
                    'utm_source' => 'facebook',
                    'utm_medium' => 'social',
                    'utm_campaign' => 'bayou_blood_cup_launch',
                    'is_active' => true,
                    'updated_at' => $now,
                ]);
        } else {
            DB::table('campaign_links')->insert([
                'slug' => 'bayou-blood-cup',
                'label' => 'Bayou Blood Cup Launch',
                'target_url' => '/cups/bayou-blood-cup',
                'utm_source' => 'facebook',
                'utm_medium' => 'social',
                'utm_campaign' => 'bayou_blood_cup_launch',
                'clicks_count' => 0,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_link_clicks');
        Schema::dropIfExists('campaign_links');
    }
};
