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
        {--dry-run : Read-only plan mode}
        {--apply : Apply approved CREATE fields for the five audited weapons}
        {--item= : One of the five audited weapon slugs}';

    protected $description = 'Preview or safely apply additive Bayou ballistics for five audited weapons.';

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
        $only = trim((string) $this->option('item'));
        if ($only !== '' && ! in_array($only, BayouBallisticsApplyService::AUDIT_SLUGS, true)) {
            $this->error('--item must be one of the five audited weapon slugs.');
            return self::FAILURE;
        }
        $slugs = $only === '' ? BayouBallisticsApplyService::AUDIT_SLUGS : [$only];
        $items = EquipmentItem::query()->where('source_status','active')->where('item_type','weapon')
            ->with(['family', 'stats.definition', 'ammo'])->get();
        $rows = [];
        $applyRows = [];
        $failed = 0;
        $written = 0;
        foreach ($slugs as $index => $slug) {
            if ($index > 0) usleep(750_000);
            try {
                $page = $source->preview($slug);
                $match = $matcher->match($page, $items);
                if ($match['item'] === null) {
                    if ($apply) {
                        $applyRows[] = [$slug, 'identity', '—', $page['name'], $page['source_url'],
                            '0', 'REVIEW_REQUIRED', $match['reason']];
                    } else {
                        $rows[] = [$slug, $page['source_url'], 'identity', '—', $page['name'],
                            'REVIEW_REQUIRED', '0', $match['reason']];
                    }
                    continue;
                }
                if ($apply) {
                    $result = $applyService->apply($match['item'], $page);
                    $written += $result['written'];
                    foreach ($result['rows'] as $row) {
                        $applyRows[] = [
                            $row['item'], $row['field'], $this->format($row['current_hnt']),
                            $this->format($row['bayou']), $row['source_page'],
                            (string) $row['confidence'], $row['result'], $row['reason'] ?? '—',
                        ];
                    }
                    continue;
                }
                $plan = $planner->plan($match['item'], $page, $match['confidence']);
                foreach ($plan['rows'] as $row) {
                    $rows[] = [
                        $row['item'], $row['source_page'], $row['field'],
                        $this->format($row['current_hnt']), $this->format($row['bayou']),
                        $row['action'], (string) $row['confidence'], $row['reason'] ?? '—',
                    ];
                }
            } catch (Throwable $exception) {
                $failed++;
                if ($apply) {
                    $applyRows[] = [$slug, 'source/apply', '—', '—', 'bayou_index', '0',
                        'REVIEW_REQUIRED', $exception->getMessage()];
                } else {
                    $rows[] = [$slug, '—', 'source', '—', '—', 'REVIEW_REQUIRED', '0', $exception->getMessage()];
                }
            }
        }

        if ($apply) {
            $this->info('Bayou ballistics apply: CREATE only; source bayou_index.');
            $this->table(['Item', 'Field', 'Old value', 'New value', 'Source', 'Confidence', 'Result', 'Reason'], $applyRows);
            $this->line('Pages: '.count($slugs).'; errors: '.$failed.'; canonical values written: '.$written.'.');
            return $failed === 0 ? self::SUCCESS : self::FAILURE;
        }

        $this->info('Bayou ballistics plan: READ-ONLY; source bayou_index; no apply mode.');
        $this->table(['Item', 'Bayou page', 'Field', 'Current HNT', 'Bayou', 'Action', 'Confidence', 'Reason'], $rows);
        $this->line('Pages: '.count($slugs).'; source errors: '.$failed.'; DB writes: 0.');

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }

    private function format(mixed $value): string
    {
        return $value === null ? '—' : (string) $value;
    }
}
