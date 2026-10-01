<?php

namespace App\Services\Equipment;

use App\Models\EquipmentItem;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class WikiGgEquipmentSource
{
    public const BASE_URL = 'https://huntshowdown.wiki.gg';

    public function key(): string
    {
        return 'wiki_gg';
    }

    public function name(): string
    {
        return 'Hunt: Showdown 1896 Wiki';
    }

    public function pageTitle(EquipmentItem $item): string
    {
        $prefix = match ($item->item_type) {
            'weapon' => 'Weapons',
            'tool' => 'Tools',
            'consumable' => 'Consumables',
            default => throw new RuntimeException('wiki.gg prototype supports weapons, tools and consumables only.'),
        };

        if ($item->item_type === 'weapon' && $item->family && $item->family->name) {
            $family = trim((string) $item->family->name);
            $name = trim((string) $item->name);

            if ($name !== $family && str_starts_with($name, $family.' ')) {
                $variant = trim(substr($name, strlen($family)));

                if ($variant !== '') {
                    return $prefix.'/'.$this->wikiPath($family).'/'.$this->wikiPath($variant);
                }
            }
        }

        return $prefix.'/'.$this->wikiPath((string) $item->name);
    }

    public function preview(EquipmentItem $item): array
    {
        $pageTitle = $this->pageTitle($item);
        $wikitext = $this->fetchWikitext($pageTitle);
        $templateName = match ($item->item_type) {
            'weapon' => 'Infobox Weapon',
            'tool' => 'Infobox Tool',
            'consumable' => 'Infobox Consumable',
            default => throw new RuntimeException('Unsupported item type.'),
        };

        $template = $this->extractFirstTemplate($wikitext, $templateName);
        if ($template === null) {
            throw new RuntimeException("wiki.gg page found, but {$templateName} was not found: {$pageTitle}");
        }

        $params = $this->parseTemplateParameters($template);
        $stats = $this->statsFromParams($params);

        return [
            'source_key' => $this->key(),
            'source_name' => $this->name(),
            'page_title' => $pageTitle,
            'page_url' => self::BASE_URL.'/wiki/'.str_replace('%2F', '/', rawurlencode($pageTitle)),
            'name' => $this->cleanWikiText($params['Title'] ?? $item->name),
            'price' => $this->number($params['Price'] ?? null),
            'slot_size' => $this->number($params['Size'] ?? null),
            'ammo_type' => $this->cleanWikiText($params['Ammo Type'] ?? null),
            'update' => $this->cleanWikiText($params['Update'] ?? null),
            'unlock' => $this->cleanWikiText($params['Unlock'] ?? null),
            'loaded' => $this->cleanWikiText($params['Loaded'] ?? null),
            'reserve' => $this->cleanWikiText($params['Extra'] ?? null),
            'quantity' => $this->number($params['Quantity'] ?? null),
            'rarity' => $this->cleanWikiText($params['Rarity'] ?? null),
            'stats' => $stats,
            'recommended_traits' => $this->recommendedTraits($wikitext),
            'skins' => $this->skinTitles($wikitext, $item->item_type),
            'ammo_types' => $this->ammoTypes($wikitext),
            'patch_history' => $this->patchHistory($wikitext),
        ];
    }

    public function fetchWikitext(string $pageTitle): string
    {
        $response = Http::acceptJson()
            ->withHeaders([
                'User-Agent' => 'HNT.ROCKS Arsenal Wiki Sync/1.0 (+https://hnt.rocks)',
            ])
            ->connectTimeout(8)
            ->timeout(20)
            ->retry(2, 400, throw: false)
            ->get(self::BASE_URL.'/api.php', [
                'action' => 'parse',
                'page' => $pageTitle,
                'prop' => 'wikitext',
                'format' => 'json',
                'formatversion' => 2,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException("wiki.gg API unavailable: HTTP {$response->status()}");
        }

        $payload = $response->json();
        if (! is_array($payload)) {
            throw new RuntimeException('wiki.gg API returned invalid JSON.');
        }

        if (isset($payload['error'])) {
            $message = is_array($payload['error']) ? ($payload['error']['info'] ?? 'unknown API error') : 'unknown API error';
            throw new RuntimeException("wiki.gg API error for {$pageTitle}: {$message}");
        }

        $wikitext = $payload['parse']['wikitext'] ?? null;
        if (! is_string($wikitext) || $wikitext === '') {
            throw new RuntimeException("wiki.gg returned no wikitext for {$pageTitle}.");
        }

        if (strlen($wikitext) > 2_000_000) {
            throw new RuntimeException("wiki.gg page is unexpectedly large: {$pageTitle}");
        }

        return $wikitext;
    }

    private function statsFromParams(array $params): array
    {
        $map = [
            'Damage' => 'damage',
            'Drop Range' => 'dropRange',
            'Rate of Fire' => 'rateOfFire',
            'Cycle Time' => 'cycleTime',
            'Spread' => 'spread',
            'Sway' => 'sway',
            'Vertical Recoil' => 'recoil',
            'Reload Speed' => 'reload',
            'Muzzle Velocity' => 'muzzleVelocity',
            'Swap Speed' => 'swapSpeed',
            'Effect Radius' => 'radius',
            'Effect Duration' => 'effectDuration',
            'Throw Range' => 'throwRange',
            'Fuse Timer' => 'fuseTimer',
            'Melee Damage' => 'melee',
            'Heavy Melee Damage' => 'heavyMelee',
            'Stamina Consumption' => 'stamina',
            'Heavy Stamina Consumption' => 'heavyStamina',
            'Throw Stamina Consumption' => 'throwStamina',
        ];

        $stats = [];
        foreach ($map as $wikiKey => $key) {
            if (! array_key_exists($wikiKey, $params)) {
                continue;
            }

            $value = $this->numericOrText($params[$wikiKey]);
            if ($value !== null && $value !== '') {
                $stats[$key] = $value;
            }
        }

        return $stats;
    }

    private function recommendedTraits(string $wikitext): array
    {
        $section = $this->section($wikitext, 'Recommended Traits');
        if ($section === null) {
            return [];
        }

        $traits = [];
        if (preg_match_all('/\{\{\s*Trait\s*\|\s*([^|}\n]+).*?\}\}/i', $section, $matches)) {
            foreach ($matches[1] as $match) {
                $value = $this->cleanWikiText($match);
                if ($value) {
                    $traits[] = $value;
                }
            }
        }

        return array_values(array_unique($traits));
    }

    private function ammoTypes(string $wikitext): array
    {
        $section = $this->section($wikitext, 'Ammo Types');
        if ($section === null) {
            return [];
        }

        $values = [];
        if (preg_match_all('/\{\{\s*(?:Ammo|Custom Ammo|Ammo Type)\s*\|\s*([^|}\n]+).*?\}\}/i', $section, $matches)) {
            foreach ($matches[1] as $match) {
                $value = $this->cleanWikiText($match);
                if ($value) {
                    $values[] = $value;
                }
            }
        }

        return array_values(array_unique($values));
    }

    private function skinTitles(string $wikitext, string $itemType): array
    {
        $templateName = match ($itemType) {
            'weapon' => 'Infobox Weapon Skin',
            'tool' => 'Infobox Tool Skin',
            'consumable' => 'Infobox Consumable Skin',
            default => null,
        };

        if ($templateName === null) {
            return [];
        }

        $titles = [];
        foreach ($this->extractAllTemplates($wikitext, $templateName) as $template) {
            $params = $this->parseTemplateParameters($template);
            $title = $this->cleanWikiText($params['Title'] ?? null);
            if ($title) {
                $titles[] = $title;
            }
        }

        return array_values(array_unique($titles));
    }

    private function patchHistory(string $wikitext): array
    {
        $section = $this->section($wikitext, 'Update History');
        if ($section === null) {
            return [];
        }

        $history = [];
        $lines = preg_split('/\R/', $section) ?: [];
        $current = null;

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '{|') || str_starts_with($line, '|-') || str_starts_with($line, '!') || $line === '|}') {
                continue;
            }

            if (str_starts_with($line, '|')) {
                $cells = preg_split('/\|\|/', ltrim($line, '| '), 2);
                if (count($cells) === 2) {
                    $patch = $this->cleanWikiText($cells[0]);
                    $note = $this->cleanWikiText($cells[1]);
                    if ($patch || $note) {
                        $current = ['patch' => $patch, 'note' => $note];
                        $history[] = $current;
                    }
                    continue;
                }

                if ($history) {
                    $extra = $this->cleanWikiText(ltrim($line, '| '));
                    if ($extra) {
                        $index = array_key_last($history);
                        $history[$index]['note'] = trim(($history[$index]['note'] ?? '').' '.$extra);
                    }
                }
            }
        }

        return array_values($history);
    }

    private function section(string $wikitext, string $heading): ?string
    {
        $pattern = '/^==\s*'.preg_quote($heading, '/').'\s*==\s*$(.*?)(?=^==[^=].*?==\s*$|\z)/msi';
        if (! preg_match($pattern, $wikitext, $match)) {
            return null;
        }

        return trim($match[1]);
    }

    private function extractFirstTemplate(string $wikitext, string $templateName): ?string
    {
        $templates = $this->extractAllTemplates($wikitext, $templateName);

        return $templates[0] ?? null;
    }

    private function extractAllTemplates(string $wikitext, string $templateName): array
    {
        $templates = [];
        $offset = 0;
        $needle = '{{'.$templateName;

        while (($start = stripos($wikitext, $needle, $offset)) !== false) {
            $depth = 0;
            $length = strlen($wikitext);
            $end = null;

            for ($index = $start; $index < $length - 1; $index++) {
                $pair = substr($wikitext, $index, 2);
                if ($pair === '{{') {
                    $depth++;
                    $index++;
                    continue;
                }
                if ($pair === '}}') {
                    $depth--;
                    $index++;
                    if ($depth === 0) {
                        $end = $index + 1;
                        break;
                    }
                }
            }

            if ($end === null) {
                break;
            }

            $templates[] = substr($wikitext, $start, $end - $start);
            $offset = $end;
        }

        return $templates;
    }

    private function parseTemplateParameters(string $template): array
    {
        $inner = trim(substr($template, 2, -2));
        $parts = $this->splitTopLevel($inner, '|');
        array_shift($parts);

        $params = [];
        foreach ($parts as $part) {
            $position = strpos($part, '=');
            if ($position === false) {
                continue;
            }

            $key = trim(substr($part, 0, $position));
            $value = trim(substr($part, $position + 1));
            if ($key !== '') {
                $params[$key] = $value;
            }
        }

        return $params;
    }

    private function splitTopLevel(string $value, string $separator): array
    {
        $parts = [];
        $buffer = '';
        $curlyDepth = 0;
        $squareDepth = 0;
        $length = strlen($value);

        for ($index = 0; $index < $length; $index++) {
            $pair = substr($value, $index, 2);

            if ($pair === '{{') {
                $curlyDepth++;
                $buffer .= $pair;
                $index++;
                continue;
            }
            if ($pair === '}}') {
                $curlyDepth = max(0, $curlyDepth - 1);
                $buffer .= $pair;
                $index++;
                continue;
            }
            if ($pair === '[[') {
                $squareDepth++;
                $buffer .= $pair;
                $index++;
                continue;
            }
            if ($pair === ']]') {
                $squareDepth = max(0, $squareDepth - 1);
                $buffer .= $pair;
                $index++;
                continue;
            }

            if ($value[$index] === $separator && $curlyDepth === 0 && $squareDepth === 0) {
                $parts[] = $buffer;
                $buffer = '';
                continue;
            }

            $buffer .= $value[$index];
        }

        $parts[] = $buffer;

        return $parts;
    }

    private function cleanWikiText(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $text = trim((string) $value);
        if ($text === '') {
            return null;
        }

        $text = preg_replace('/<!--.*?-->/s', '', $text) ?? $text;
        $text = preg_replace_callback('/\[\[([^\]|]+)\|([^\]]+)\]\]/', fn ($m) => $m[2], $text) ?? $text;
        $text = preg_replace_callback('/\[\[([^\]]+)\]\]/', fn ($m) => $m[1], $text) ?? $text;
        $text = preg_replace_callback('/\{\{\s*Rarity\s*\|\s*([^|}]+).*?\}\}/i', fn ($m) => $m[1], $text) ?? $text;
        $text = preg_replace('/\{\{[^{}]*\}\}/', '', $text) ?? $text;
        $text = preg_replace('/<[^>]+>/', '', $text) ?? $text;
        $text = str_replace(["'''", "''"], '', $text);
        $text = preg_replace('/\s+/', ' ', $text) ?? $text;

        return ($text = trim($text)) !== '' ? $text : null;
    }

    private function number(mixed $value): int|float|null
    {
        $clean = $this->cleanWikiText($value);
        if ($clean === null || ! preg_match('/-?\d+(?:\.\d+)?/', str_replace(',', '', $clean), $match)) {
            return null;
        }

        $number = (float) $match[0];

        return floor($number) === $number ? (int) $number : $number;
    }

    private function numericOrText(mixed $value): int|float|string|null
    {
        $clean = $this->cleanWikiText($value);
        if ($clean === null) {
            return null;
        }

        $normalized = str_replace(',', '', $clean);
        if (preg_match('/^-?\d+(?:\.\d+)?$/', $normalized)) {
            $number = (float) $normalized;

            return floor($number) === $number ? (int) $number : $number;
        }

        return $clean;
    }

    private function wikiPath(string $value): string
    {
        return str_replace(' ', '_', trim($value));
    }
}
