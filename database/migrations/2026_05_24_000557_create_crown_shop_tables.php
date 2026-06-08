<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crown_shop_items', function (Blueprint $table): void {
            $table->id();
            $table->string('key', 100)->unique();
            $table->string('type', 50)->index();
            $table->string('slot', 50)->nullable()->index();
            $table->string('name_de', 120);
            $table->string('name_en', 120)->nullable();
            $table->text('description_de')->nullable();
            $table->text('description_en')->nullable();
            $table->unsignedInteger('price')->default(0)->index();
            $table->string('rarity', 40)->default('common')->index();
            $table->string('icon', 80)->nullable();
            $table->string('preview_class', 120)->nullable();
            $table->string('image_path', 255)->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_limited')->default(false)->index();
            $table->timestamp('available_from')->nullable();
            $table->timestamp('available_until')->nullable();
            $table->unsignedInteger('sort_order')->default(100)->index();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'available_from', 'available_until']);
            $table->index(['type', 'slot']);
        });

        Schema::create('crown_inventory_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('shop_item_id')->constrained('crown_shop_items')->cascadeOnDelete();
            $table->timestamp('purchased_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'shop_item_id']);
            $table->index(['user_id', 'created_at']);
        });

        Schema::create('crown_equipped_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('slot', 50);
            $table->foreignId('inventory_item_id')->constrained('crown_inventory_items')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'slot']);
            $table->unique('inventory_item_id');
        });

        $now = now();
        DB::table('crown_shop_items')->insert([
            [
                'key' => 'avatar_frame_bayou_iron',
                'type' => 'avatar_frame',
                'slot' => 'avatar_frame',
                'name_de' => 'Bayou-Iron Avatarrahmen',
                'name_en' => 'Bayou Iron Avatar Frame',
                'description_de' => 'Ein dunkler, rostiger Rahmen für dein Profilbild. Käufe landen in deinem Inventar und können dort vorbereitet werden.',
                'description_en' => 'A dark rusty frame for your profile image. Purchases land in your inventory and can be prepared there.',
                'price' => 120,
                'rarity' => 'common',
                'icon' => 'shield',
                'preview_class' => 'bayou-iron',
                'sort_order' => 10,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'avatar_frame_blood_crown',
                'type' => 'avatar_frame',
                'slot' => 'avatar_frame',
                'name_de' => 'Blood-Crown Avatarrahmen',
                'name_en' => 'Blood Crown Avatar Frame',
                'description_de' => 'Ein seltener Kronenrahmen mit dunklen Rot-Akzenten.',
                'description_en' => 'A rare crown frame with dark red accents.',
                'price' => 450,
                'rarity' => 'epic',
                'icon' => 'crown',
                'preview_class' => 'blood-crown',
                'sort_order' => 20,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'profile_banner_dark_bayou',
                'type' => 'profile_banner',
                'slot' => 'profile_banner',
                'name_de' => 'Dark-Bayou Profilbanner',
                'name_en' => 'Dark Bayou Profile Banner',
                'description_de' => 'Ein düsteres Banner für deinen späteren Profilkopf.',
                'description_en' => 'A dark banner for your future profile header.',
                'price' => 300,
                'rarity' => 'rare',
                'icon' => 'image',
                'preview_class' => 'dark-bayou',
                'sort_order' => 30,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'username_glow_ember',
                'type' => 'username_effect',
                'slot' => 'username_effect',
                'name_de' => 'Ember Username Glow',
                'name_en' => 'Ember Username Glow',
                'description_de' => 'Ein dezenter Glut-Effekt für deinen Namen.',
                'description_en' => 'A subtle ember effect for your username.',
                'price' => 250,
                'rarity' => 'rare',
                'icon' => 'sparkle',
                'preview_class' => 'ember-glow',
                'sort_order' => 40,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'username_effect_bloodmarked',
                'type' => 'username_effect',
                'slot' => 'username_effect',
                'name_de' => 'Bloodmarked Username',
                'name_en' => 'Bloodmarked Username',
                'description_de' => 'Ein blutiger Namenseffekt für deinen HNT-Auftritt.',
                'description_en' => 'A bloody username effect for your HNT presence.',
                'price' => 400,
                'rarity' => 'epic',
                'icon' => 'drop',
                'preview_class' => 'bloodmarked',
                'sort_order' => 50,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'title_bayou_survivor',
                'type' => 'title',
                'slot' => 'profile_title',
                'name_de' => 'Titel: Bayou Survivor',
                'name_en' => 'Title: Bayou Survivor',
                'description_de' => 'Ein kosmetischer Profil-Titel ohne Gameplay-Vorteil.',
                'description_en' => 'A cosmetic profile title with no gameplay advantage.',
                'price' => 150,
                'rarity' => 'common',
                'icon' => 'tag',
                'preview_class' => 'title-bayou-survivor',
                'sort_order' => 60,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'title_bloodmarked',
                'type' => 'title',
                'slot' => 'profile_title',
                'name_de' => 'Titel: Bloodmarked',
                'name_en' => 'Title: Bloodmarked',
                'description_de' => 'Ein legendärer kosmetischer Titel für dein Profil.',
                'description_en' => 'A legendary cosmetic title for your profile.',
                'price' => 500,
                'rarity' => 'legendary',
                'icon' => 'seal-warning',
                'preview_class' => 'title-bloodmarked',
                'sort_order' => 70,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'moment_overlay_blood_splash',
                'type' => 'moment_overlay',
                'slot' => 'moment_overlay',
                'name_de' => 'Moment-Overlay: Blood Splash',
                'name_en' => 'Moment Overlay: Blood Splash',
                'description_de' => 'Ein Overlay für das spätere Moment Studio.',
                'description_en' => 'An overlay for the future Moment Studio.',
                'price' => 500,
                'rarity' => 'epic',
                'icon' => 'film-strip',
                'preview_class' => 'blood-splash',
                'sort_order' => 80,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('crown_equipped_items');
        Schema::dropIfExists('crown_inventory_items');
        Schema::dropIfExists('crown_shop_items');
    }
};
