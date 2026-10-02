<?php

namespace App\Console\Commands;

use App\Models\EquipmentItem;
use App\Services\Equipment\BayouBallisticsPlanner;
use App\Services\Equipment\BayouIndexBallisticsSource;
use App\Services\Equipment\BayouWeaponMatcher;
use Illuminate\Console\Command;
use Throwable;

class PreviewArsenalBayouBallistics extends Command
{
    private const SLUGS = [
        '1865-carbine', 'drilling', 'berthier-1892-deadeye',
        'mosin-nagant-sniper', 'sparks',
    ];

    protected $signature = 'arsenal:bayou-ballistics
        {--dry-run : Required read-only plan mode}
        {--item= : One of the five audited weapon slugs}';

    protected $description = 'Read-only Bayou ballistics plan for the five audited weapons; no apply mode.';

    public function handle(
        BayouIndexBallisticsSource $source,
        BayouWeaponMatcher $matcher,
        BayouBallisticsPlanner $planner,
    ): int {
        if (! $this->option('dry-run')) {
            $this->error('This command only supports --dry-run.');
            return self::FAILURE;
        }
        $only = trim((string) $this->option('item'));
        if ($only !== '' && ! in_array($only, self::SLUGS, true)) {
            $this->error('--item must be one of the five audited weapon slugs.');
            return self::FAILURE;
        }
        $slugs = $only === '' ? self::SLUGS : [$only];
        $items = EquipmentItem::query()->where('source_status','active')->where('item_type','weapon')
            ->with(['family', 'stats.definition', 'ammo'])->get();
        $rows = [];
        $failed = 0;
        foreach ($slugs as $index => $slug) {
            if ($index > 0) usleep(750_000);
            try {
                $page = $source->preview($slug);
                $match = $matcher->match($page, $items);
                if ($match['item'] === null) {
                    $rows[] = [$slug, $page['source_url'], 'identity', '—', $page['name'],
                        'REVIEW_REQUIRED', '0', $match['reason']];
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
                $rows[] = [$slug, '—', 'source', '—', '—', 'REVIEW_REQUIRED', '0', $exception->getMessage()];
            }
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
