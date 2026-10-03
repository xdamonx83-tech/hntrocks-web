<?php

namespace Tests\Unit\Maps;

use App\Services\Maps\Imports\ExternalMapCoordinateTransformer;
use App\Services\Maps\Imports\KamilleMapMarkerProvider;
use App\Services\Maps\Imports\SourceFormatException;
use App\Services\Maps\Imports\SourceUnavailableException;
use App\Support\Maps\MapMarkerRegistry;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class MapMarkerImportProviderTest extends TestCase
{
    private function fixture(): array
    {
        return json_decode(file_get_contents(base_path('tests/Fixtures/maps/kamille-stillwater-sample.json')), true, 512, JSON_THROW_ON_ERROR);
    }

    public function test_provider_parses_only_required_structured_fields_and_stable_ids(): void
    {
        $provider = new KamilleMapMarkerProvider;
        $first = $provider->parseMap('stillwater-bayou', $this->fixture());
        $second = $provider->parseMap('stillwater-bayou', $this->fixture());

        $this->assertCount(9, $first);
        $this->assertSame($first, $second);
        $this->assertSame('EasterEgg001', $first[0]['source_key']);
        $this->assertSame('easter_eggs', $first[0]['source_category']);
        $this->assertSame('easter_egg', $first[0]['type']);
        $this->assertSame(2855.0, $first[0]['x']);
        $this->assertSame(3312.0, $first[0]['y']);
        $this->assertArrayNotHasKey('u', $first[0]);
        $this->assertArrayNotHasKey('d', $first[0]);
        $this->assertSame('beast', $first[3]['type']);
        $this->assertSame('hunting', $first[5]['subtype']);
        $this->assertSame('watch', $first[6]['subtype']);
        $this->assertSame('scout', $first[7]['subtype']);
    }

    public function test_provider_map_and_category_mapping_match_current_source(): void
    {
        $provider = new KamilleMapMarkerProvider;

        $this->assertSame([1, 2, 3, 4], array_column($provider->maps(), 'id'));
        $this->assertSame('brutes', $provider->categories()['beast']['source_category']);
        $this->assertSame('wild_targets', $provider->categories()['wild_target']['source_category']);
        $this->assertSame('scout_towers', $provider->categories()['tower:scout']['source_category']);
        $this->assertTrue(MapMarkerRegistry::all()['cash']['visible']);
        $this->assertFalse(MapMarkerRegistry::all()['cash']['importable']);
    }

    public function test_coordinate_transformer_swaps_axes_and_scales_without_mirroring(): void
    {
        $transformer = new ExternalMapCoordinateTransformer;

        $this->assertSame(['x' => 778.0, 'y' => 2001.5], $transformer->transform([4003, 1556], 2048, 2048));
        $this->assertSame(['x' => 0.0, 'y' => 0.0], $transformer->transform([0, 0], 2048, 2048));
        $this->assertSame(['x' => 2048.0, 'y' => 2048.0], $transformer->transform([4096, 4096], 2048, 2048));

        // Independent, same-location legacy tower references from all four HNT maps.
        foreach ([
            [[850, 1737], [869.188, 424.157]],
            [[2389, 965], [482.609, 1195.13]],
            [[3700, 2812], [1408.43, 1850.33]],
            [[1315, 3334], [1665.94, 659.386]],
        ] as [$external, $hnt]) {
            $point = $transformer->transform($external, 2048, 2048);
            $this->assertLessThan(3, hypot($point['x'] - $hnt[0], $point['y'] - $hnt[1]));
        }
    }

    public function test_unknown_wild_target_subtype_is_not_guessed(): void
    {
        $data = $this->fixture();
        $data['wild_targets'][0]['boss'] = 'future_boss';

        $markers = (new KamilleMapMarkerProvider)->parseMap('stillwater-bayou', $data);

        $this->assertNull($markers[1]['subtype']);
    }

    public function test_out_of_bounds_source_marker_is_skipped_and_reported(): void
    {
        Log::spy();
        $data = $this->fixture();
        $data['easter_eggs'][0]['c'] = [433, 5206];
        $provider = new KamilleMapMarkerProvider;

        $markers = $provider->parseMap('stillwater-bayou', $data);

        $this->assertCount(8, $markers);
        $this->assertSame(['easter_eggs' => 1], $provider->outOfBounds('stillwater-bayou'));
        $this->assertSame(['EasterEgg001'], $provider->outOfBoundsKeys('stillwater-bayou'));
        Log::shouldHaveReceived('warning')->once();
    }

    public function test_unknown_category_is_logged_and_skipped(): void
    {
        Log::spy();
        $data = $this->fixture();
        $data['unexpected_creatures'] = [['id' => 'Unknown00001', 'c' => [100, 200]]];

        $markers = (new KamilleMapMarkerProvider)->parseMap('stillwater-bayou', $data);

        $this->assertCount(9, $markers);
        Log::shouldHaveReceived('warning')->once();
    }

    public function test_changed_source_identity_aborts_instead_of_guessing_coordinate_key(): void
    {
        $data = $this->fixture();
        unset($data['beetles'][0]['id']);

        $this->expectException(SourceFormatException::class);
        (new KamilleMapMarkerProvider)->parseMap('stillwater-bayou', $data);
    }

    public function test_provider_fetches_json_only_and_rejects_changed_manifest(): void
    {
        Http::fake([
            'hunt.kamille.ovh/maps/cache/poi-types.json' => Http::response([
                'easter_egg' => ['categories' => 'easter_eggs'],
            ]),
        ]);

        $this->expectException(SourceFormatException::class);
        (new KamilleMapMarkerProvider)->markers('stillwater-bayou');
    }

    public function test_unavailable_source_raises_a_specific_error(): void
    {
        Http::fake(['hunt.kamille.ovh/maps/cache/poi-types.json' => Http::response('', 503)]);

        $this->expectException(SourceUnavailableException::class);
        (new KamilleMapMarkerProvider)->markers('stillwater-bayou');
    }

    public function test_missing_expected_category_aborts_the_preview(): void
    {
        $data = $this->fixture();
        unset($data['workbenches']);

        $this->expectException(SourceFormatException::class);
        (new KamilleMapMarkerProvider)->parseMap('stillwater-bayou', $data);
    }
}
