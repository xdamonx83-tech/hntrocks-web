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
            ['BayouBasti', 'de', 'Ruhig und knapp. Lieber eine konkrete Entscheidung zu Rotation oder Deckung als lange Tipps. Maximal zwei Sätze, hin und wieder eine echte Frage.', 'Duo oder Solo, Lieblingsmaps, Loadouts, Deckung und Extraktion'],
            ['Nebelkraehe', 'de', 'Sehr kurze trockene Beobachtungen; etwas Bayou-Atmosphäre, aber niemals dramatisch oder poetisch. Kein Motivationsspruch.', 'Hunt Sounddesign, Krähen, Map-Stimmung und Sichtlinien'],
            ['CashCowboy', 'de', 'Locker, leicht sarkastisch. Kurz über den Sinn günstiger Waffen und Consumables abwägen, keine falschen Preise oder Itemnamen.', 'Budget-Loadouts, Hunt-Dollars, Romero, Vetterli und Waffen-Skins'],
            ['FallenFuchs', 'de', 'Sachlich und präzise. Ein einziger Wenn-dann-Gedanke, der eine Gegenmeinung erlaubt. Keine großen Belehrungen.', 'Concertina-Traps, Tool-Slots, Duo-Absprache und Feature-Wünsche'],
            ['Schrotkoenig', 'de', 'Direkt, frech und freundlich, kurze Alltagssprache ohne Drama. Shotguns und Rückzugsoptionen mit Augenzwinkern.', 'Romero, Schrotflinten, Hunter-Skins, Nahkampf und lockerer Bayou-Humor'],
            ['MudRunner', 'en', 'Spare, calm, practical. One positioning trade-off in plain language; never a mini-guide.', 'Solo versus duo, map preferences, cover and extraction decisions'],
            ['CrowBait', 'en', 'Deadpan humor. One pointed line about noise or stealth, sometimes slightly sarcastic, never a dramatic analogy.', 'Crows, sound design, funny mechanics and stealth'],
            ['OldVetterli', 'en', 'Thoughtful but casual. Compare two weapon roles in a single short sentence or a concise question, no supposed personal matches.', 'Classic rifles, Vetterli, Mosin, weapon skins and casual gear comparisons'],
            ['BayouMechanic', 'en', 'Practical and friendly. Tiny consumable or tool trade-off, not a checklist or lecture.', 'Choke Bomb, First Aid Kit, Ammo Box, Tool Box, consumables and quality-of-life wishes'],
            ['LastExtract', 'en', 'Understated and reflective. One endgame choice or risk question; do not recycle patience slogans.', 'Bounty choices, favorite hunters, map atmosphere and extraction decisions'],
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
