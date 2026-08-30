<?php

namespace Database\Seeders;

use App\Models\Arcade\ArcadeGame;
use Illuminate\Database\Seeder;

class ArcadeGameSeeder extends Seeder
{
    public function run(): void
    {
        ArcadeGame::query()->updateOrCreate(['key' => 'hunt-wins'], ['name_de' => 'Hunt gewinnt', 'name_en' => 'Hunt Wins', 'description_de' => 'Das taktische Duell für zwei Hunter.', 'description_en' => 'The tactical duel for two hunters.', 'type' => 'native', 'status' => 'disabled', 'sort_order' => 10, 'min_players' => 2, 'max_players' => 2, 'casual_enabled' => true, 'ranked_enabled' => true, 'client_engine_key' => 'hunt-wins', 'game_version' => 1, 'reward_settings' => []]);
    }
}
