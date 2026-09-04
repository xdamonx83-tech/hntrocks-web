<?php

namespace Database\Seeders;

use App\Models\Arcade\ArcadeGame;
use Illuminate\Database\Seeder;

class ArcadeGameSeeder extends Seeder
{
    public function run(): void
    {
        $huntWins = ArcadeGame::query()->firstOrCreate(
            ['key' => 'hunt-wins'],
            [
                'name_de' => 'Hunt gewinnt',
                'name_en' => 'Hunt Wins',
                'description_de' => 'Das taktische Duell für zwei Hunter.',
                'description_en' => 'The tactical duel for two hunters.',
                'type' => 'native',
                'status' => 'disabled',
                'sort_order' => 10,
                'min_players' => 2,
                'max_players' => 2,
                'casual_enabled' => true,
                'ranked_enabled' => true,
                'client_engine_key' => 'hunt-wins',
                'game_version' => 1,
                'reward_settings' => [],
            ],
        );

        $memory = ArcadeGame::query()->firstOrCreate(
            ['key' => 'hunt-memory'],
            [
                'name_de' => 'Hunt Memory',
                'name_en' => 'Hunt Memory',
                'description_de' => 'Ein düsteres Memory-Duell für zwei Hunter.',
                'description_en' => 'A dark memory duel for two hunters.',
                'type' => 'native',
                'status' => 'disabled',
                'sort_order' => ((int) $huntWins->sort_order) + 10,
                'min_players' => 2,
                'max_players' => 2,
                'casual_enabled' => true,
                'ranked_enabled' => true,
                'client_engine_key' => 'hunt-memory',
                'game_version' => 1,
                'reward_settings' => [],
            ],
        );

        $huntersMark = ArcadeGame::query()->firstOrCreate(
            ['key' => 'hunters-mark'],
            [
                'name_de' => "Hunter's Mark",
                'name_en' => "Hunter's Mark",
                'description_de' => 'Ein düsteres Zeichen-Duell für zwei Hunter.',
                'description_en' => 'A dark mark duel for two hunters.',
                'type' => 'native',
                'status' => 'disabled',
                'sort_order' => ((int) $memory->sort_order) + 10,
                'min_players' => 2,
                'max_players' => 2,
                'casual_enabled' => true,
                'ranked_enabled' => true,
                'client_engine_key' => 'hunters-mark',
                'game_version' => 1,
                'reward_settings' => [],
            ],
        );

        ArcadeGame::query()->firstOrCreate(
            ['key' => 'hunters-gallows'],
            [
                'name_de' => "Hunter's Gallows",
                'name_en' => "Hunter's Gallows",
                'description_de' => 'Ein düsteres Wortduell für zwei Hunter.',
                'description_en' => 'A dark word duel for two hunters.',
                'type' => 'native',
                'status' => 'disabled',
                'sort_order' => ((int) $huntersMark->sort_order) + 10,
                'min_players' => 2,
                'max_players' => 2,
                'casual_enabled' => true,
                'ranked_enabled' => true,
                'client_engine_key' => 'hunters-gallows',
                'game_version' => 1,
                'reward_settings' => [],
            ],
        );
    }
}
