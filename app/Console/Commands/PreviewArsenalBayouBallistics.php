<?php

namespace App\Console\Commands;

use App\Models\EquipmentItem;
use App\Services\Equipment\BayouBallisticsApplyService;
use App\Services\Equipment\BayouBallisticsPlanner;
use App\Services\Equipment\BayouIndexBallisticsSource;
use App\Services\Equipment\BayouWeaponMatcher;
use Illuminate\Console\Command;
use Throwable;

class PreviewArsenalBayouBallistics extends Command
{
    protected $signature = 'arsenal:bayou-ballistics
        {--dry-run : Read-only planning mode}
        {--apply : CREATE-only canonical apply}
        {--weapon= : Limit to one active HNT weapon slug}
        {--item= : Backward-compatible alias for --weapon}
        {--report= : JSON audit output path}';

    protected $description = 'Audit all active HNT weapon ammo modes against the reviewed public Bayou build.';

    public function handle(
        BayouIndexBallisticsSource $source,
        BayouWeaponMatcher $matcher,
        BayouBallisticsPlanner $planner,
        BayouBallisticsApplyService $applyService,
    ): int {
        $apply = (bool) $this->option('apply');
        if ($apply === (bool) $this->option('dry-run')) {
            $this->error('Choose exactly one of --dry-run or --apply.');
            return self::FAILURE;
        }
        $only = trim((string) ($this->option('weapon') ?: $this->option('item')));
        if ($this->option('weapon') && $this->option('item') && $this->option('weapon') !== $this->option('item')) {
            $this->error('--weapon and --item disagree.');
            return self::FAILURE;
        }
        $query = EquipmentItem::query()->where('source_status', 'active')->where('item_type', 'weapon');
        if ($only !== '') $query->where('slug', $only);
        $items = $query->with(['family', 'stats.definition', 'ammo.falloffPoints'])->orderBy('slug')->get();
        if ($only !== '' && $items->isEmpty()) {
            $this->error('No active HNT weapon has that slug.');
            return self::FAILURE;
        }
        $catalog = $source->catalog();
        $weapons = $source->catalogWeapons();
        $summary = array_fill_keys([
            'TOTAL WEAPONS', 'MATCHED WEAPONS', 'UNMATCHED WEAPONS', 'AMBIGUOUS WEAPONS',
            'TOTAL AMMO MODES', 'MATCHED AMMO MODES', 'UNMATCHED AMMO MODES',
            'CREATE', 'UNCHANGED', 'REVIEW_REQUIRED', 'SKIP', 'UNSUPPORTED',
            'SOURCE_ERRORS', 'DB_WRITES', 'BULLET_DROP_CAPABLE_WEAPONS',
            'BULLET_DROP_UNSUPPORTED_WEAPONS',
        ], 0);
        $summary['TOTAL WEAPONS'] = $items->count();
        $report = [
            'source' => ['key' => 'bayou_index', 'build_id' => $catalog['build_id'],
                'data_url' => $catalog['data_url'], 'data_sha256' => $catalog['data_sha256'],
                'model_url' => $catalog['model_url'], 'model_sha256' => $catalog['model_sha256']],
            'observed_at' => now()->toIso8601String(),
            'mode' => $apply ? 'apply' : 'dry-run',
            'weapons' => [],
        ];
        foreach ($items as $item) {
            $summary['TOTAL AMMO MODES'] += $item->ammo->count();
            $entry = [
                'hnt_slug' => $item->slug, 'hnt_name' => $item->name,
                'source_url' => null, 'source_identity' => null,
                'match_confidence' => 0, 'match_action' => 'UNMATCHED',
                'ammo' => [], 'fields' => [],
            ];
            try {
                $sourceSlug = match ($item->slug) {
                    'burgess' => 'burgess-folding',
                    'burgess-bayonet' => 'burgess-folding-bayonet',
                    'burgess-trauma' => 'burgess-folding-trauma',
                    default => $item->slug,
                };
                $page = $weapons[$sourceSlug] ?? null;
                if ($page === null) {
                    $summary['UNMATCHED WEAPONS']++;
                    $summary['UNMATCHED AMMO MODES'] += $item->ammo->count();
                    $entry['reason'] = 'No exact public weapon ID in the reviewed build.';
                    $report['weapons'][] = $entry;
                    continue;
                }
                $entry['source_url'] = $page['source_url'];
                $entry['source_identity'] = ['id' => $page['id'], 'name' => $page['name']];
                $match = $matcher->match($page, $items);
                if ($match['item']?->getKey() !== $item->getKey() || $match['confidence'] < 95) {
                    $summary['AMBIGUOUS WEAPONS']++;
                    $summary['UNMATCHED AMMO MODES'] += $item->ammo->count();
                    $entry['match_action'] = 'REVIEW_REQUIRED';
                    $entry['reason'] = $match['reason'] ?? 'Weapon identity did not match this item.';
                    $report['weapons'][] = $entry;
                    continue;
                }
                $summary['MATCHED WEAPONS']++;
                $entry['match_action'] = 'MATCHED';
                $entry['match_confidence'] = $match['confidence'];
                $plan = $planner->planCatalog($item, $page, $match['confidence']);
                $summary['MATCHED AMMO MODES'] += count($plan['matches']);
                $summary['UNMATCHED AMMO MODES'] += $item->ammo->count() - count($plan['matches']);
                $hasFlight = false;
                foreach ($item->ammo as $ammo) {
                    $modeMatch = $plan['matches'][$ammo->key] ?? null;
                    $mode = $modeMatch === null ? null : collect($page['modes'])
                        ->firstWhere('id', $modeMatch['source_ammo_id']);
                    $hasFlight = $hasFlight || ($mode['bullet_drop'] ?? null) !== null;
                    $entry['ammo'][] = [
                        'hnt_key' => $ammo->key, 'hnt_name' => $ammo->name,
                        'hnt_ammo_type' => $ammo->ammo_type,
                        'source_ammo_id' => $mode['id'] ?? null,
                        'source_name' => $mode['name'] ?? null,
                        'match_confidence' => $modeMatch['confidence'] ?? 0,
                        'action' => $mode === null ? 'REVIEW_REQUIRED' : 'MATCHED',
                        'projectile_kind' => $mode['projectile_kind'] ?? null,
                    ];
                }
                $summary[$hasFlight ? 'BULLET_DROP_CAPABLE_WEAPONS' : 'BULLET_DROP_UNSUPPORTED_WEAPONS']++;
                $result = $apply ? $applyService->applyCatalog($item) : null;
                $summary['DB_WRITES'] += $result['written'] ?? 0;
                $entry['fields'] = $result['rows'] ?? $plan['rows'];
                foreach ($entry['fields'] as $row) {
                    $action = $apply ? ($row['result'] ?? $row['action']) : $row['action'];
                    if (isset($summary[$action])) $summary[$action]++;
                }
            } catch (Throwable $exception) {
                $summary['SOURCE_ERRORS']++;
                $entry['match_action'] = 'SOURCE_ERROR';
                $entry['reason'] = $exception->getMessage();
            }
            $report['weapons'][] = $entry;
        }
        $report['summary'] = $summary;
        $path = trim((string) $this->option('report'));
        if ($path === '') {
            $path = storage_path('app/arsenal/audits/bayou-ballistics-'.$catalog['build_id'].'-'.now()->format('Ymd-His').'.json');
        }
        if (! is_dir(dirname($path))) mkdir(dirname($path), 0775, true);
        file_put_contents($path, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)."\n");
        $this->info($apply ? 'Bayou ballistics CREATE-only apply' : 'Bayou ballistics READ-ONLY dry-run');
        foreach ($summary as $key => $value) $this->line($key.': '.$value);
        $this->line('REPORT: '.$path);
        return $summary['SOURCE_ERRORS'] === 0 ? self::SUCCESS : self::FAILURE;
    }
}
