<?php

namespace App\Services\Equipment;

use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class BayouIndexBallisticsSource
{
    private const BASE_URL = 'https://bayouindex.com/weapons/';
    private ?array $catalog = null;

    /** A reviewed public-build snapshot; normal imports never fetch BayouIndex. */
    public function catalog(): array
    {
        if ($this->catalog !== null) return $this->catalog;
        $path = resource_path('bayou-ballistics-snapshot.json');
        $catalog = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        if (($catalog['source_key'] ?? null) !== 'bayou_index'
            || ! preg_match('/\A[a-f0-9]{64}\z/', (string) ($catalog['data_sha256'] ?? ''))
            || ! preg_match('/\A[a-f0-9]{64}\z/', (string) ($catalog['model_sha256'] ?? ''))
            || ! is_array($catalog['weapons'] ?? null)) {
            throw new RuntimeException('Invalid reviewed Bayou public snapshot.');
        }
        $this->catalog = $catalog;
        return $catalog;
    }

    /** @return array<string, array> */
    public function catalogWeapons(): array
    {
        $weapons = [];
        foreach ($this->catalog()['weapons'] as $weapon) {
            $slug = (string) ($weapon['id'] ?? '');
            if (! preg_match('/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/', $slug)
                || isset($weapons[$slug]) || ! is_array($weapon['modes'] ?? null)) {
                throw new RuntimeException('Duplicate or invalid weapon in Bayou snapshot.');
            }
            $weapons[$slug] = $weapon + [
                'source_page_slug' => $slug,
                'source_key' => 'bayou_index',
                'observed_at' => $this->catalog()['generated_at'],
                'payload_hash' => hash('sha256', json_encode($weapon, JSON_THROW_ON_ERROR)),
            ];
        }
        return $weapons;
    }

    public function bulletDropProfile(array $mode, array $weapon): ?array
    {
        $flight = $mode['bullet_drop'] ?? null;
        $catalog = $this->catalog();
        if (! is_array($flight) || ($mode['projectile_kind'] ?? null) !== 'standard_bullet') return null;
        return $flight + [
            'max_distance_m' => $catalog['max_distance_m'],
            'head_size_m' => $catalog['head_size_m'],
            'reference_aim' => $catalog['reference_aim'],
            'zone_offsets_m' => $catalog['zone_offsets_m'],
            'source_model_verified' => true,
            'game_rules_verified' => false,
            'status' => 'available',
            'source' => [
                'key' => 'bayou_index', 'url' => $weapon['source_url'],
                'data_url' => $catalog['data_url'], 'model_url' => $catalog['model_url'],
                'build_id' => $catalog['build_id'], 'observed_at' => $catalog['generated_at'],
                'source_ammo_id' => $mode['id'],
            ],
        ];
    }

    public function preview(string $slug): array
    {
        if (! preg_match('/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/', $slug)) {
            throw new RuntimeException('Invalid Bayou weapon slug.');
        }

        $url = self::BASE_URL.$slug.'/';
        $response = Http::timeout(15)
            ->withOptions(['allow_redirects' => false])
            ->withHeaders(['User-Agent' => 'HNT.ROCKS ballistics read-only preview'])
            ->get($url);

        if ($response->status() !== 200 || ! str_contains(strtolower((string) $response->header('Content-Type')), 'text/html')) {
            throw new RuntimeException('Bayou weapon page is not public HTML: '.$url);
        }
        if (strlen($response->body()) > 512_000) {
            throw new RuntimeException('Bayou weapon page exceeds the preview size limit.');
        }

        return $this->parse($response->body(), $slug, now()->toIso8601String());
    }

    public function parse(string $html, string $slug, string $observedAt): array
    {
        $previous = libxml_use_internal_errors(true);
        try {
            $document = new DOMDocument();
            $loaded = $document->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
        if (! $loaded) throw new RuntimeException('Bayou weapon HTML could not be parsed.');

        $xpath = new DOMXPath($document);
        $name = $this->clean($xpath->evaluate('string((//h1)[1])'));
        if ($name === '') throw new RuntimeException('Bayou weapon page has no visible name.');

        $specs = [];
        $cells = $xpath->query('//div[contains(concat(" ", normalize-space(@class), " "), " spec-cell ") or contains(concat(" ", normalize-space(@class), " "), " more-cell ")]');
        foreach ($cells as $cell) {
            $label = $this->clean($xpath->evaluate('string(./dt[1])', $cell));
            if ($label !== '' && ! array_key_exists($label, $specs)) {
                $specs[$label] = $this->clean($xpath->evaluate('string(./dd[1])', $cell));
            }
        }

        $profile = $this->clean($xpath->evaluate('string((//section[contains(concat(" ", normalize-space(@class), " "), " profile ")])[1])'));
        $baseDamage = preg_match('/Base damage\s+(\d+(?:\.\d+)?)/i', $profile, $match)
            ? $this->number($match[1], 0, 1000) : null;

        $multipliers = [
            'headMultiplier' => null,
            'upperTorsoMultiplier' => null,
            'torsoMultiplier' => null,
            'armMultiplier' => null,
            'legMultiplier' => null,
        ];
        $zones = ['Head' => 'headMultiplier', 'Upper torso' => 'upperTorsoMultiplier',
            'Torso' => 'torsoMultiplier', 'Arm' => 'armMultiplier', 'Leg' => 'legMultiplier'];
        foreach ($xpath->query('//section[contains(concat(" ", normalize-space(@class), " "), " profile ")]//div[contains(concat(" ", normalize-space(@class), " "), " rowhead ")]') as $row) {
            $text = $this->clean($row->textContent);
            foreach ($zones as $label => $key) {
                if (str_starts_with($text, $label.' ') && preg_match('/×\s*(\d+(?:\.\d+)?)/u', $text, $match)) {
                    $multipliers[$key] = $this->number($match[1], 0, 100);
                }
            }
        }

        $ammoType = null;
        foreach ($xpath->query('//img[@alt]') as $image) {
            if (preg_match('/\A(compact|medium|long|shell) ammunition\z/i', trim($image->getAttribute('alt')), $match)) {
                $ammoType = ucfirst(strtolower($match[1]));
                break;
            }
        }
        $ammo = $specs['Ammo'] ?? '';
        $reserve = preg_match('/\A\s*\d+\s*\/\s*(\d+)/', $ammo, $match) ? (float) $match[1] : null;

        $fields = ['baseDamage' => $baseDamage, 'zoom' => $this->number($specs['Zoom'] ?? null, 0, 100)] + $multipliers;
        $checks = [
            'damage' => $this->number($specs['Damage'] ?? null, 0, 1000),
            'cycleTime' => $this->number($specs['Cycle'] ?? null, 0, 100),
            'dropRange' => $this->number($specs['Drop'] ?? null, 0, 10000),
            'reserve' => $reserve,
        ];
        $payload = ['name' => $name, 'ammo_type' => $ammoType, 'fields' => $fields, 'checks' => $checks];

        return [
            'source_key' => 'bayou_index',
            'source_page_slug' => $slug,
            'source_url' => self::BASE_URL.$slug.'/',
            'observed_at' => $observedAt,
            'name' => $name,
            'ammo_type' => $ammoType,
            'family' => null,
            'variant' => null,
            'fields' => $fields,
            'checks' => $checks,
            'payload_hash' => hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR)),
        ];
    }

    private function clean(string $value): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', $value));
    }

    private function number(?string $value, float $minimum, float $maximum): ?float
    {
        if ($value === null || ! preg_match('/-?\d+(?:\.\d+)?/', $value, $match)) return null;
        $number = (float) $match[0];
        return is_finite($number) && $number > $minimum && $number <= $maximum ? $number : null;
    }
}
