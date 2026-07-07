<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('badges', function (Blueprint $table): void {
            $table->string('name_de', 120)->nullable()->after('name');
            $table->string('name_en', 120)->nullable()->after('name_de');
            $table->text('description_de')->nullable()->after('description');
            $table->text('description_en')->nullable()->after('description_de');
        });

        Schema::table('quests', function (Blueprint $table): void {
            $table->string('name_de', 140)->nullable()->after('name');
            $table->string('name_en', 140)->nullable()->after('name_de');
            $table->text('description_de')->nullable()->after('description');
            $table->text('description_en')->nullable()->after('description_de');
        });

        DB::table('badges')
            ->whereNull('name_de')
            ->update(['name_de' => DB::raw('name')]);

        DB::table('badges')
            ->whereNull('description_de')
            ->update(['description_de' => DB::raw('description')]);

        DB::table('quests')
            ->whereNull('name_de')
            ->update(['name_de' => DB::raw('name')]);

        DB::table('quests')
            ->whereNull('description_de')
            ->update(['description_de' => DB::raw('description')]);

        foreach ($this->badgeTranslations() as $slug => $translation) {
            DB::table('badges')->where('slug', $slug)->update($translation);
        }

        foreach ($this->questTranslations() as $slug => $translation) {
            DB::table('quests')->where('slug', $slug)->update($translation);
        }
    }

    public function down(): void
    {
        Schema::table('quests', function (Blueprint $table): void {
            $table->dropColumn(['name_de', 'name_en', 'description_de', 'description_en']);
        });

        Schema::table('badges', function (Blueprint $table): void {
            $table->dropColumn(['name_de', 'name_en', 'description_de', 'description_en']);
        });
    }

    private function badgeTranslations(): array
    {
        return [
            'alpha' => [
                'description_en' => 'You took part in the alpha.',
            ],
            'conversation-starter' => [
                'name_en' => 'Conversation Starter',
                'description_en' => 'You wrote your first comment.',
            ],
            'level-5' => [
                'description_en' => 'You reached level 5.',
            ],
            'cup-contender' => [
                'name_en' => 'Cup Participant',
                'description_en' => 'You joined a Cup team.',
            ],
            'lfg-hunter' => [
                'name_en' => 'LFG Hunter',
                'description_en' => 'You created an LFG.',
            ],
            'moment-maker' => [
                'name_en' => 'Moment Maker',
                'description_en' => 'You published your first Moment.',
            ],
            'profile-complete' => [
                'name_en' => 'Profile Completed',
                'description_en' => 'Your profile is fully filled out.',
            ],
        ];
    }

    private function questTranslations(): array
    {
        return [
            'first-step-bayou' => [
                'name_en' => 'First Step into the Bayou',
            ],
            'first-wall-post' => [
                'name_en' => 'First Wall Post',
            ],
            'first-comment' => [
                'name_en' => 'Join the Conversation',
            ],
            'talk-to-hunters' => [
                'name_en' => 'Talk to the Hunters',
            ],
            'create-lfg' => [
                'name_en' => 'Looking for Teammates',
            ],
            'show-reaction' => [
                'name_en' => 'Show a Reaction',
            ],
            'first-moment' => [
                'name_en' => 'Moment Maker',
            ],
        ];
    }
};
