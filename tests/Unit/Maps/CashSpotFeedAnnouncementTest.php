<?php

namespace Tests\Unit\Maps;

use App\Models\HntMap;
use App\Models\HntMapCashSpotSubmission;
use App\Models\HntMapMarker;
use App\Services\Maps\CashSpotFeedAnnouncement;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class CashSpotFeedAnnouncementTest extends TestCase
{
    public function test_deep_link_contains_exact_marker_id_and_coordinates(): void
    {
        $map = new HntMap();
        $map->slug = 'lawson-delta';

        $submission = new HntMapCashSpotSubmission();
        $submission->id = 19;

        $marker = new HntMapMarker();
        $marker->id = 81;
        $marker->x = 1862;
        $marker->y = 1091;

        $method = new ReflectionMethod(CashSpotFeedAnnouncement::class, 'mapUrl');
        $url = $method->invoke(new CashSpotFeedAnnouncement(), $map, $submission, $marker);

        $this->assertSame(
            '/maps/lawson-delta?cash_marker=81&x=1862&y=1091&cash_spot=19',
            $url,
        );
    }
}
