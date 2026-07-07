<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach ($this->badgeTranslations() as $translation) {
            $this->fillBadgeTranslation(
                $translation['slugs'],
                $translation['names'],
                $translation['values'],
            );
        }

        foreach ($this->questTranslations() as $translation) {
            $this->fillQuestTranslation(
                $translation['slugs'],
                $translation['names'],
                $translation['values'],
            );
        }
    }

    public function down(): void
    {
        // Data-only backfill. Existing translations should not be removed.
    }

    private function fillBadgeTranslation(array $slugs, array $names, array $values): void
    {
        $this->fillTranslation('badges', $slugs, $names, $values);
    }

    private function fillQuestTranslation(array $slugs, array $names, array $values): void
    {
        $this->fillTranslation('quests', $slugs, $names, $values);
    }

    private function fillTranslation(string $table, array $slugs, array $names, array $values): void
    {
        $records = DB::table($table)
            ->where(function ($query) use ($slugs, $names): void {
                $query->whereIn('slug', $slugs)
                    ->orWhereIn('name', $names)
                    ->orWhereIn('name_de', $names);
            })
            ->get();

        foreach ($records as $record) {
            $updates = [];

            foreach (['name_en', 'description_en'] as $field) {
                if (! array_key_exists($field, $values)) {
                    continue;
                }

                $current = $record->{$field} ?? null;
                if ($current !== null && trim((string) $current) !== '') {
                    continue;
                }

                $updates[$field] = $values[$field];
            }

            if ($updates === []) {
                continue;
            }

            DB::table($table)->where('id', $record->id)->update($updates);
        }
    }

    private function badgeTranslations(): array
    {
        return [
            [
                'slugs' => ['alpha'],
                'names' => ['Alpha'],
                'values' => [
                    'name_en' => 'Alpha',
                    'description_en' => 'You took part in the alpha.',
                ],
            ],
            [
                'slugs' => ['early-hunter'],
                'names' => ['Early Hunter'],
                'values' => [
                    'name_en' => 'Early Hunter',
                    'description_en' => 'You collected your first XP.',
                ],
            ],
            [
                'slugs' => ['profile-scout'],
                'names' => ['Profil-Scout'],
                'values' => [
                    'name_en' => 'Profile Scout',
                    'description_en' => 'Your profile is at least half complete.',
                ],
            ],
            [
                'slugs' => ['profile-complete'],
                'names' => ['Profil komplett', 'Profile Completed'],
                'values' => [
                    'name_en' => 'Profile Completed',
                    'description_en' => 'Your profile is fully filled out.',
                ],
            ],
            [
                'slugs' => ['wall-starter'],
                'names' => ['Wall-Starter'],
                'values' => [
                    'name_en' => 'Wall Starter',
                    'description_en' => 'You created your first feed post.',
                ],
            ],
            [
                'slugs' => ['conversation-starter'],
                'names' => ['Gesprächsstarter', 'Gespraechsstarter'],
                'values' => [
                    'name_en' => 'Conversation Starter',
                    'description_en' => 'You wrote your first comment.',
                ],
            ],
            [
                'slugs' => ['team-founder'],
                'names' => ['Team-Gründer', 'Team-Gruender'],
                'values' => [
                    'name_en' => 'Team Founder',
                    'description_en' => 'You created a team.',
                ],
            ],
            [
                'slugs' => ['lfg-hunter'],
                'names' => ['LFG-Hunter'],
                'values' => [
                    'name_en' => 'LFG Hunter',
                    'description_en' => 'You created an LFG.',
                ],
            ],
            [
                'slugs' => ['team-recruiter'],
                'names' => ['Recruiter', 'Team-Recruiter'],
                'values' => [
                    'name_en' => 'Team Recruiter',
                    'description_en' => 'You created a Team LFG.',
                ],
            ],
            [
                'slugs' => ['media-scout'],
                'names' => ['Medien-Scout'],
                'values' => [
                    'name_en' => 'Media Scout',
                    'description_en' => 'You uploaded media to the media library.',
                ],
            ],
            [
                'slugs' => ['moment-maker'],
                'names' => ['Moment-Maker'],
                'values' => [
                    'name_en' => 'Moment Maker',
                    'description_en' => 'You published your first Moment.',
                ],
            ],
            [
                'slugs' => ['cup-organizer'],
                'names' => ['Cup-Organizer'],
                'values' => [
                    'name_en' => 'Cup Organizer',
                    'description_en' => 'You created your first Cup.',
                ],
            ],
            [
                'slugs' => ['cup-contender'],
                'names' => ['Cup-Teilnehmer'],
                'values' => [
                    'name_en' => 'Cup Participant',
                    'description_en' => 'You joined a Cup team.',
                ],
            ],
            [
                'slugs' => ['level-5'],
                'names' => ['Veteran I'],
                'values' => [
                    'name_en' => 'Veteran I',
                    'description_en' => 'You reached level 5.',
                ],
            ],
        ];
    }

    private function questTranslations(): array
    {
        return [
            [
                'slugs' => ['first-step-bayou', 'first-step-into-bayou', 'first-bayou-step'],
                'names' => ['Erster Schritt ins Bayou'],
                'values' => [
                    'name_en' => 'First Step into the Bayou',
                    'description_en' => 'Complete your first step in the Bayou.',
                ],
            ],
            [
                'slugs' => ['first-wall-post'],
                'names' => ['Erster Wall-Beitrag'],
                'values' => [
                    'name_en' => 'First Wall Post',
                    'description_en' => 'Create your first feed post.',
                ],
            ],
            [
                'slugs' => ['first-comment'],
                'names' => ['Misch dich ein'],
                'values' => [
                    'name_en' => 'Join the Conversation',
                    'description_en' => 'Write your first comment.',
                ],
            ],
            [
                'slugs' => ['talk-to-hunters', 'talk-with-hunters'],
                'names' => ['Sprich mit den Huntern'],
                'values' => [
                    'name_en' => 'Talk to the Hunters',
                    'description_en' => 'Talk to other Hunters.',
                ],
            ],
            [
                'slugs' => ['create-lfg'],
                'names' => ['Mitspieler gesucht'],
                'values' => [
                    'name_en' => 'Looking for Teammates',
                    'description_en' => 'Create a global LFG.',
                ],
            ],
            [
                'slugs' => ['show-reaction', 'first-reaction', 'react-to-post'],
                'names' => ['Zeig Reaktion'],
                'values' => [
                    'name_en' => 'Show a Reaction',
                    'description_en' => 'React to a post.',
                ],
            ],
            [
                'slugs' => ['first-moment'],
                'names' => ['Moment-Macher', 'Erster Moment'],
                'values' => [
                    'name_en' => 'Moment Maker',
                    'description_en' => 'Publish your first Moment.',
                ],
            ],
            [
                'slugs' => ['create-team'],
                'names' => ['Team gründen', 'Team gruenden'],
                'values' => [
                    'name_en' => 'Create a Team',
                    'description_en' => 'Create your first team.',
                ],
            ],
            [
                'slugs' => ['create-team-lfg'],
                'names' => ['Recruiting starten'],
                'values' => [
                    'name_en' => 'Start Recruiting',
                    'description_en' => 'Create a Team LFG.',
                ],
            ],
            [
                'slugs' => ['upload-media'],
                'names' => ['Erstes Medium'],
                'values' => [
                    'name_en' => 'First Media Upload',
                    'description_en' => 'Upload media to your media library.',
                ],
            ],
            [
                'slugs' => ['first-cup-team'],
                'names' => ['Cup-Einstieg'],
                'values' => [
                    'name_en' => 'Cup Entry',
                    'description_en' => 'Create your first Cup team.',
                ],
            ],
        ];
    }
};
