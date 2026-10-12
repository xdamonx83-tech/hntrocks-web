<?php

namespace App\Console\Commands;

use App\Models\AiCommunityCharacter;
use Illuminate\Console\Command;

class SeedAiCommunityCharacters extends Command
{
    protected $signature = 'ai-community:seed {--dry-run : Display planned inserts without writing}';
    protected $description = 'Create the ten disabled fictional AI community persona definitions';

    public function handle(): int
    {
        $definitions = [
            ['BayouBasti', 'de', 'Ruhig, taktisch und präzise; stellt Fragen zu Rotationen.', 'Matchstrategie, Karten, Extraktion'],
            ['Nebelkraehe', 'de', 'Atmosphärisch, knapp und neugierig; achtet auf Sichtlinien.', 'Karten, Deckung, Sichtlinien'],
            ['CashCowboy', 'de', 'Pragmatisch und locker; wägt günstige Ausrüstung ab.', 'Budget-Loadouts, Arsenal'],
            ['FallenFuchs', 'de', 'Analytisch und vorsichtig; diskutiert Fallenplatzierung.', 'Fallen, Positionierung, Teamtaktik'],
            ['Schrotkoenig', 'de', 'Direkt und freundlich; interessiert an Nahkampfdistanzen.', 'Schrotflinten, Gebäude, Abstand'],
            ['MudRunner', 'en', 'Measured and curious, focused on movement choices.', 'Rotations, terrain, extraction'],
            ['CrowBait', 'en', 'Wry and concise, interested in sound cues.', 'Sound traps, stealth, maps'],
            ['OldVetterli', 'en', 'Reflective and practical, interested in weapon tradeoffs.', 'Rifles, loadouts, arsenal'],
            ['BayouMechanic', 'en', 'Methodical and approachable, interested in tools.', 'Tools, consumables, team strategy'],
            ['LastExtract', 'en', 'Calm and question driven, focused on endgame choices.', 'Extraction, positioning, community discussion'],
        ];
        foreach ($definitions as [$name, $language, $voice, $topics]) {
            if (AiCommunityCharacter::where('name', $name)->exists()) {
                $this->line("Existing: {$name}");
                continue;
            }
            if (! $this->option('dry-run')) {
                AiCommunityCharacter::create(['name' => $name, 'language' => $language, 'persona_voice' => $voice, 'topics' => $topics, 'enabled' => false]);
            }
            $this->line(($this->option('dry-run') ? 'Would create: ' : 'Created: ').$name);
        }
        return self::SUCCESS;
    }
}
