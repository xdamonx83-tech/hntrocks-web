<?php
/**
 * EXPLICITLY PRIVATE, READ-ONLY exporter of original Laravel generation messages.
 *
 * No .env, OpenAI keys, database, accounts, posts or requests.
 * Usage: php scripts/ai_community_v9_preview_export.php /private/out.json
 * Refuses overwrites. Must run from an isolated code checkout with its own
 * (non-production) vendor/autoload.php. If not available, use HNT staging autoload.
 */
declare(strict_types=1);

$root = realpath(dirname(__DIR__));
$destination = $argv[1] ?? null;
if (!$root || !$destination || is_file($destination) || file_exists($destination)) {
    fwrite(STDERR, "STOP: missing source/destination or output exists\n"); exit(2);
}
$autoload = is_file($root.'/vendor/autoload.php')
    ? $root.'/vendor/autoload.php'
    : '/home/codex/hntrocks-web-current/vendor/autoload.php';
if (!is_file($autoload)) {fwrite(STDERR,"STOP: composer autoload not found\n"); exit(2);}
require_once $autoload;
spl_autoload_register(static function (string $name) use ($root): void {
    if (!str_starts_with($name, 'App\\')) return;
    $file = $root.'/app/'.str_replace('\\', '/', substr($name, 4)).'.php';
    if (is_file($file)) require_once $file;
}, true, true);

// Eight private V9 smoke-test cases with fixed real Laravel content guidance.
// Do not reuse V8 blind scores as a claim of independent improvement.
// Character names are synthetic and will not be provisioned.
$cases = [
    ['BayouBasti', 'de', 'Kurz und locker; keine eigenen Spielrunden erfinden', 'Hunter-Looks', 'open_question', 'favorite_hunter_look', 'headline_question'],
    ['CashCowboy', 'de', 'Direkte Vergleiche ohne erfundene Preiszahlen', 'Romero und Vetterli', 'gear_choice', 'romero_vetterli', 'preference_question'],
    ['FallenFuchs', 'de', 'Präzise und knapp, keine Produktwerbung', 'Loadout-Funktionen', 'feature_wish', 'loadout_qol', 'wish_question'],
    ['Nebelkraehe', 'de', 'Sehr kurz ohne dramatische Naturbilder', 'Krähen', 'sound_and_mood', 'crow_audio', 'casual_one_liner'],
    ['MudRunner', 'en', 'Plain everyday map comparison', 'Stillwater DeSalle', 'map_chat', 'stillwater_vs_desalle', 'headline_question'],
    ['OldVetterli', 'en', 'One understated personal aesthetic preference without claimed ownership', 'Hunter skins', 'hunter_styles', 'simple_vs_flashy', 'mild_opinion'],
    ['CrowBait', 'en', 'Short dry joke, not a fabricated gameplay moment', 'Romero', 'bayou_banter', 'romero_fan_club', 'casual_one_liner'],
    ['BayouMechanic', 'en', 'One specific hypothetical UI improvement, avoid invented patch', 'Tool selection', 'feature_wish', 'tool_slot_ui', 'wish_question'],
]

use App\Models\AiCommunityCharacter;
use App\Services\AiCommunityGenerationService;
use App\Services\AiCommunityDraftQualityGuard;
use App\Services\AiCommunityStyleLibrary;
use App\Services\AiCommunityEditorialAngles;
use App\Services\AiCommunityPostFormatSelector;

if (count($cases) !== 8 || count(array_filter($cases, static fn ($r) => $r[1] === 'de')) !== 4 ||
    count(array_filter($cases, static fn ($r) => $r[1] === 'en')) !== 4) {
    fwrite(STDERR,"STOP: incorrect blind test composition\n"); exit(3);
}
$service = new AiCommunityGenerationService(new AiCommunityDraftQualityGuard());
$catalog = AiCommunityEditorialAngles::catalog();
$styles = AiCommunityStyleLibrary::styles();
$record = [];
$uniqueTopics = [];
foreach ($cases as $i => [$name,$language,$voice,$topics,$kind,$angleId,$styleId]) {
    if (!isset($catalog[$kind], $styles[$styleId])) {
        fwrite(STDERR,"STOP: missing style or post kind\n"); exit(3);
    }
    $angle = null;
    foreach ($catalog[$kind] as $item) {
        if ($item['id'] === $angleId) {$angle = $item; break;}
    }
    $style = $styles[$styleId];
    if (!$angle || $style['source_requirement'] !== 'none' ||
        !in_array($kind, $style['post_kinds'],true) ||
        !in_array($language,$style['languages'],true)) {
        fwrite(STDERR,"STOP: incompatible angle/style\n"); exit(3);
    }
    $uniqueTopics[$angleId] = true;
    $persona = new AiCommunityCharacter([
        'name' => $name, 'language' => $language, 'persona_voice' => $voice, 'topics' => $topics,
    ]);
    $messages = $service->promptContext($persona, [], $kind, $angle, $style);
    if (count($messages)!==2 || $messages[0]['role']!=='system' || $messages[1]['role']!=='user') {
        fwrite(STDERR,"STOP: malformed prompt\n"); exit(3);
    }
    $record[] = [
        'id' => sprintf('V9-%02d',$i+1),
        'name' => $name, 'language' => $language, 'post_kind'=>$kind,
        'editorial_angle'=>$angleId, 'style_key'=>$styleId, 'expected_ending'=>$style['ending'],
        'messages'=>$messages,
    ];
}
if (count($uniqueTopics) < 8) {fwrite(STDERR,"STOP: insufficient distinct topics\n"); exit(3);}
$commit=trim((string) shell_exec('git -C '.escapeshellarg($root).' rev-parse HEAD 2>/dev/null'));
if (!preg_match('/^[a-f0-9]{40}$/',$commit)) {fwrite(STDERR,"STOP: Git revision not found\n");exit(3);}
$json=json_encode(['type'=>'isolated_read_only_blind_generation_prompts',
 'source_commit'=>$commit,'cases'=>$record],
 JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE|JSON_THROW_ON_ERROR)."\n";
$oldmask=umask(0077);
$handle=fopen($destination,'x');
if (!$handle) {fwrite(STDERR,"STOP: cannot write private prompt file\n");exit(3);}
chmod($destination,0600);
fwrite($handle,$json);fclose($handle);umask($oldmask);
echo "HNT_AI_V9_PROMPTS_OK CASES 8 TOPICS ",count($uniqueTopics),"\n";
echo "SOURCE_COMMIT $commit\n";
echo "PROMPT_SHA256 ",hash('sha256',$json),"\n";
echo "NO_API NO_DB NO_FEED NO_ACCOUNTS\n";
