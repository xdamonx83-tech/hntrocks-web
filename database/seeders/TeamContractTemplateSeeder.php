<?php

namespace Database\Seeders;

use App\Models\TeamContractTemplate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class TeamContractTemplateSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('team_contract_templates')) {
            return;
        }

        foreach ((array) config('team_progression.contracts.templates', []) as $key => $definition) {
            TeamContractTemplate::query()->updateOrCreate(
                ['key' => $key],
                [
                    'name_key' => $definition['name_key'],
                    'description_key' => $definition['description_key'],
                    'category' => $definition['category'],
                    'metric' => $definition['metric'],
                    'target_value' => (int) $definition['target'],
                    'minimum_contributors' => (int) $definition['minimum_contributors'],
                    'duration_days' => $definition['duration_days'],
                    'is_repeatable' => (bool) $definition['repeatable'],
                    'cooldown_days' => $definition['cooldown_days'],
                    'team_xp_reward' => (int) $definition['team_xp_reward'],
                    'rocks_reward' => (int) $definition['rocks_reward'],
                    'is_active' => true,
                    'configuration' => $definition['configuration'] ?? null,
                ]
            );
        }
    }
}
