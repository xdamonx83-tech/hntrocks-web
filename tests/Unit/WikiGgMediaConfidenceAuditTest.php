<?php

namespace Tests\Unit;

use App\Models\EquipmentItem;
use App\Models\EquipmentSkin;
use App\Services\Equipment\WikiGgMediaConfidenceAudit;
use Illuminate\Database\Eloquent\Collection;
use PHPUnit\Framework\TestCase;

class WikiGgMediaConfidenceAuditTest extends TestCase
{
    public function test_aggregation_counts_only_safe_existing_skin_matches_as_auto_importable(): void
    {
        $item = new EquipmentItem;
        $item->slug = 'drilling';
        $item->name = 'Drilling';
        $local = new EquipmentSkin;
        $local->forceFill(['id' => 7, 'name' => 'Local', 'local_asset_path' => 'arsenal/skins/local.jpg']);
        $item->setRelation('skins', new Collection([$local]));
        $audit = new WikiGgMediaConfidenceAudit;
        $wiki = ['skins' => [
            ['name' => 'Exact', 'image' => ['url' => 'https://example.test/exact.jpg']],
            ['name' => 'Local', 'image' => ['url' => 'https://example.test/local.jpg']],
            ['name' => 'Ambiguous', 'image' => ['url' => 'https://example.test/ambiguous.jpg'], 'image_ambiguous' => true],
            ['name' => 'Missing', 'image' => null],
            ['name' => 'New', 'image' => ['url' => 'https://example.test/new.jpg']],
            ['name' => 'Fuzzy', 'image' => ['url' => 'https://example.test/fuzzy.jpg']],
        ]];
        $plan = ['skins' => [
            ['action' => 'MATCH', 'match_confidence' => 100, 'image_confidence' => 100,
                'image_action' => 'IMPORT', 'image_file' => 'Exact.jpg'],
            ['action' => 'MATCH', 'existing_skin_id' => 7, 'match_confidence' => 100, 'image_confidence' => 100,
                'image_action' => 'REVIEW_REQUIRED', 'image_reason' => 'Existing local image must be reviewed before replacement'],
            ['action' => 'MATCH', 'match_confidence' => 100, 'image_confidence' => 65,
                'image_action' => 'REVIEW_REQUIRED', 'image_file' => 'Ambiguous.jpg'],
            ['action' => 'MATCH', 'match_confidence' => 100, 'image_confidence' => 0,
                'image_action' => 'SKIP', 'image_reason' => 'No resolved image URL'],
            ['action' => 'CREATE', 'match_confidence' => 100, 'image_confidence' => 100,
                'image_action' => 'IMPORT', 'image_file' => 'New.jpg'],
            ['action' => 'MATCH', 'match_confidence' => 85, 'image_confidence' => 90,
                'image_action' => 'IMPORT', 'image_file' => 'Fuzzy.jpg'],
        ]];

        $audit->add($item, $wiki, $plan, 2);

        $this->assertSame([
            'skins_detected' => 6,
            'skin_images_resolved' => 5,
            'image_score_100' => 3,
            'image_score_80_99' => 1,
            'image_score_65_79' => 1,
            'image_score_below_65' => 1,
            'ambiguous_image_matches' => 1,
            'missing_image' => 1,
            'auto_importable' => 1,
            'review_required' => 5,
            'score_100_but_blocked' => 2,
        ], $audit->counts());
        $this->assertSame(['existing_local_image' => 1, 'no_existing_skin_match' => 1], $audit->blockedScore100Reasons());
        $this->assertSame('arsenal/skins/local.jpg', $audit->blockedScore100()[0]['local_asset_path']);
        $this->assertCount(2, $audit->problems());
        $this->assertSame('drilling', $audit->problems()[0][0]);
        $this->assertSame('Multiple equally ranked image candidates', $audit->problems()[1][6]);
    }
}
