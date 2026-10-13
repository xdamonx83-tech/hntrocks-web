<?php
/**
 * EXPLICITLY PRIVATE, READ-ONLY exporter of original Laravel generation messages.
 *
 * No .env, OpenAI keys, database, accounts, posts or requests.
 * Usage: php scripts/ai_community_blind_prompt_export.php /path/out.json
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

// 20 unseen samples relative to the V6 editorial-angle list, covering both
// languages and distinct styles; repeated angles only across languages.
// Character names are synthetic and will not be provisioned.
$cases = [
    ['BayouBasti', 'de', 'Kurz, locker und neugierig', 'Waffen', 'open_question', 'one_weapon_choice', 'headline_question'],
    ['Nebelkraehe', 'de', 'Trocken, ohne übertriebene Poesie', 'Sound', 'sound_and_mood', 'game_ambience', 'casual_one_liner'],
    ['CashCowboy', 'de', 'Budget, ironisch, keine Zahlen erfinden', 'Romero', 'small_opinion', 'romero_simplicity', 'mild_opinion'],
    ['FallenFuchs', 'de', 'Lockere Fragen statt formeller Vorschläge', 'Kosmetik UI', 'feature_wish', 'favorite_skin_ui', 'german_discussion'],
    ['Schrotkoenig', 'de', 'Wenig Worte, trockener Humor', 'Krähen', 'bayou_banter', 'crows_are_loud', 'dry_observation'],
    ['BayouBasti', 'de', 'Kurz, locker und neugierig', 'Hunter', 'hunter_styles', 'favorite_theme', 'preference_question'],
    ['Nebelkraehe', 'de', 'Trocken, ohne übertriebene Poesie', 'Maps', 'map_chat', 'lawson_mood', 'dry_observation'],
    ['CashCowboy', 'de', 'Budget, ironisch, keine Zahlen erfinden', 'Ammo Box', 'gear_choice', 'ammo_box_consumable', 'preference_question'],
    ['FallenFuchs', 'de', 'Trockene leichte Meinung', 'Tools', 'bayou_banter', 'choke_discussion', 'mild_opinion'],
    ['Schrotkoenig', 'de', 'Direkt und locker', 'Hunter-Skins', 'hunter_styles', 'cosmetic_favorite', 'headline_question'],
    ['MudRunner', 'en', 'Brief understated fan opinion', 'Maps', 'open_question', 'map_preference', 'headline_question'],
    ['CrowBait', 'en', 'Dry, informal and direct', 'Audio', 'sound_and_mood', 'sound_discussion', 'preference_question'],
    ['OldVetterli', 'en', 'Straightforward, no invented owned skins', 'Hunter looks', 'small_opinion', 'hunter_style_matters', 'mild_opinion'],
    ['BayouMechanic', 'en', 'Casual small hypothetical suggestions', 'Skins UI', 'feature_wish', 'favorite_skin_ui', 'concise_wish'],
    ['LastExtract', 'en', 'Plain conversational preference', 'Hunter', 'hunter_styles', 'favorite_theme', 'preference_question'],
    ['MudRunner', 'en', 'Brief, conversational question', 'Map audio', 'map_chat', 'favorite_map_audio', 'conversational_question'],
    ['CrowBait', 'en', 'Understated dry humor', 'Crows', 'bayou_banter', 'crows_are_loud', 'casual_one_liner'],
    ['OldVetterli', 'en', 'Simple tradeoff question', 'Ammo Box', 'gear_choice', 'ammo_box_consumable', 'conversational_question'],
    ['BayouMechanic', 'en', 'Short hypothetical UI ideas', 'Skin sorting', 'feature_wish', 'favorite_skin_ui', 'wish_question'],
    ['LastExtract', 'en', 'Understated subjective statement', 'Lawson Delta', 'map_chat', 'lawson_mood', 'mild_opinion'],
];

use App\Models\AiCommunityCharacter;
use App\Services\AiCommunityGenerationService;
use App\Services\AiCommunityDraftQualityGuard;
use App\Services\AiCommunityStyleLibrary;
use App\Services\AiCommunityEditorialAngles;
use App\Services\AiCommunityPostFormatSelector;

if (count($cases) !== 20 || count(array_filter($cases, static fn ($r) => $r[1] === 'de')) !== 10 ||
    count(array_filter($cases, static fn ($r) => $r[1] === 'en')) !== 10) {
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
        'id' => sprintf('V8-%02d',$i+1),
        'name' => $name, 'language' => $language, 'post_kind'=>$kind,
        'editorial_angle'=>$angleId, 'style_key'=>$styleId, 'expected_ending'=>$style['ending'],
        'messages'=>$messages,
    ];
}
if (count($uniqueTopics) < 12) {fwrite(STDERR,"STOP: insufficient distinct topics\n"); exit(3);}
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
echo "HNT_AI_V8_BLIND_PROMPTS_OK CASES 20 TOPICS ",count($uniqueTopics),"\n";
echo "SOURCE_COMMIT $commit\n";
echo "PROMPT_SHA256 ",hash('sha256',$json),"\n";
echo "NO_API NO_DB NO_FEED NO_ACCOUNTS\n";
