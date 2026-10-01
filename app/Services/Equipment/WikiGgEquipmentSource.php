<?php

namespace App\Services\Equipment;

use App\Models\EquipmentItem;
use DOMDocument;
use DOMElement;
use DOMXPath;
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
            default => throw new RuntimeException('wiki.gg supports weapons, tools and consumables only.'),
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

    public function preview(EquipmentItem $item, bool $withMedia = true, bool $withRevision = true): array
    {
        [$pageTitle, $page] = $this->fetchPageForItem($item, $withRevision);
        $wikitext = $page['wikitext'];

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
        $skinRows = $this->skinRows($wikitext, $item->item_type, is_string($page['html']) ? $page['html'] : '');

        $candidateFiles = array_values(array_unique(array_filter(array_merge(
            $page['images'],
            [$this->fileNameFromParam($params['image'] ?? $params['Image'] ?? null)],
            array_map(fn (array $skin) => $skin['image_file'] ?? null, $skinRows),
        ))));

        $imageInfo = $withMedia ? $this->resolveImageInfo($candidateFiles) : [];
        $baseFile = $this->bestImageFile(
            explicit: $this->fileNameFromParam($params['image'] ?? $params['Image'] ?? null),
            candidates: $page['images'],
            subject: (string) ($this->cleanWikiText($params['Title'] ?? $item->name) ?? $item->name),
            mode: 'base',
            imageInfo: $imageInfo,
        );

        $skins = array_map(function (array $skin) use ($page, $imageInfo): array {
            $file = $this->bestImageFile(
                explicit: $skin['image_file'] ?? null,
                candidates: $page['images'],
                subject: (string) ($skin['name'] ?? ''),
                mode: 'skin',
                imageInfo: $imageInfo,
            );

            return array_merge($skin, [
                'image_file' => $file,
                'image' => $file ? ($imageInfo[$file] ?? null) : null,
            ]);
        }, $skinRows);

        $history = $this->patchHistory($wikitext);
        if (! $history && is_string($page['html']) && $page['html'] !== '') {
            $history = $this->patchHistoryFromHtml($page['html']);
        }

        return [
            'source_key' => $this->key(),
            'source_name' => $this->name(),
            'revision_id' => $page['revision_id'],
            'revision_timestamp' => $page['revision_timestamp'] ?? null,
            'page_title' => $pageTitle,
            'page_url' => $this->wikiPageUrl($pageTitle),
            'family' => $this->familyNameFromPageTitle($pageTitle, $item->item_type),
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
            'skins' => $skins,
            'ammo_types' => $this->ammoTypes($wikitext),
            'patch_history' => $history,
            'base_image_file' => $baseFile,
            'base_image' => $baseFile ? ($imageInfo[$baseFile] ?? null) : null,
            'image_candidates' => count($page['images']),
        ];
    }

    private function fetchPageForItem(EquipmentItem $item, bool $withRevision = true): array
    {
        $candidates = [$this->pageTitle($item)];

        $prefix = match ($item->item_type) {
            'weapon' => 'Weapons',
            'tool' => 'Tools',
            'consumable' => 'Consumables',
            default => null,
        };

        if ($prefix !== null) {
            $direct = $prefix.'/'.$this->wikiPath((string) $item->name);
            array_unshift($candidates, $direct);

            if ($item->item_type === 'weapon') {
                $parts = preg_split('/\s+/', trim((string) $item->name)) ?: [];
                for ($split = count($parts) - 1; $split >= 1; $split--) {
                    $family = implode(' ', array_slice($parts, 0, $split));
                    $variant = implode(' ', array_slice($parts, $split));
                    if ($family !== '' && $variant !== '') {
                        $candidates[] = 'Weapons/'.$this->wikiPath($family).'/'.$this->wikiPath($variant);
                    }
                }
            }
        }

        $candidates = array_values(array_unique(array_filter($candidates)));
        $lastError = null;

        foreach ($candidates as $candidate) {
            try {
                return [$candidate, $this->fetchPage($candidate, $withRevision)];
            } catch (RuntimeException $e) {
                $lastError = $e;
            }
        }

        throw $lastError ?? new RuntimeException('No matching wiki.gg page could be resolved for '.$item->name);
    }

    private function familyNameFromPageTitle(string $pageTitle, string $itemType): ?string
    {
        if ($itemType !== 'weapon' || ! str_starts_with($pageTitle, 'Weapons/')) {
            return null;
        }

        $relative = substr($pageTitle, strlen('Weapons/'));
        $first = explode('/', $relative, 2)[0] ?? '';

        return $first !== '' ? str_replace('_', ' ', $first) : null;
    }

    public function fetchPage(string $pageTitle, bool $withRevision = true): array
    {
        $response = $this->http()->get(self::BASE_URL.'/api.php', [
            'action' => 'parse',
            'page' => $pageTitle,
            'prop' => 'wikitext|text|images',
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

        $parse = $payload['parse'] ?? null;
        if (! is_array($parse)) {
            throw new RuntimeException("wiki.gg returned no parse payload for {$pageTitle}.");
        }

        $wikitext = $parse['wikitext'] ?? null;
        if (! is_string($wikitext) || $wikitext === '') {
            throw new RuntimeException("wiki.gg returned no wikitext for {$pageTitle}.");
        }

        if (strlen($wikitext) > 2_000_000) {
            throw new RuntimeException("wiki.gg page is unexpectedly large: {$pageTitle}");
        }

        $revision = ['id' => null, 'timestamp' => null];
        if ($withRevision) {
            $pageId = isset($parse['pageid']) && is_numeric($parse['pageid']) ? (int) $parse['pageid'] : null;
            $revision = $this->resolveRevision($pageTitle, $pageId);
        }

        return [
            'wikitext' => $wikitext,
            'html' => is_string($parse['text'] ?? null) ? $parse['text'] : '',
            'images' => array_values(array_filter(array_map('strval', is_array($parse['images'] ?? null) ? $parse['images'] : []))),
            'revision_id' => $revision['id'],
            'revision_timestamp' => $revision['timestamp'],
        ];
    }

    public function fetchWikitext(string $pageTitle): string
    {
        return $this->fetchPage($pageTitle, false)['wikitext'];
    }

    private function resolveRevision(string $pageTitle, ?int $pageId): array
    {
        $query = [
            'action' => 'query',
            'prop' => 'revisions',
            'rvprop' => 'ids|timestamp',
            'rvlimit' => 1,
            'format' => 'json',
            'formatversion' => 2,
        ];

        if ($pageId !== null) {
            $query['pageids'] = $pageId;
        } else {
            $query['titles'] = $pageTitle;
            $query['redirects'] = 1;
        }

        $response = $this->http()->get(self::BASE_URL.'/api.php', $query);
        if (! $response->successful()) {
            return ['id' => null, 'timestamp' => null];
        }

        $payload = $response->json();
        $pages = is_array($payload['query']['pages'] ?? null) ? $payload['query']['pages'] : [];
        $page = $pages[0] ?? null;
        $revision = is_array($page) && is_array($page['revisions'][0] ?? null) ? $page['revisions'][0] : null;

        return [
            'id' => is_array($revision) && isset($revision['revid']) && is_numeric($revision['revid']) ? (int) $revision['revid'] : null,
            'timestamp' => is_array($revision) && is_string($revision['timestamp'] ?? null) ? $revision['timestamp'] : null,
        ];
    }

    public function resolveImageInfo(array $fileNames): array
    {
        $fileNames = array_values(array_unique(array_filter(array_map(
            fn ($name) => $this->normalizeFileTitle((string) $name),
            $fileNames
        ))));

        if (! $fileNames) {
            return [];
        }

        $result = [];

        foreach (array_chunk($fileNames, 25) as $chunk) {
            $titles = implode('|', array_map(fn ($name) => 'File:'.$name, $chunk));
            $response = $this->http()->get(self::BASE_URL.'/api.php', [
                'action' => 'query',
                'prop' => 'imageinfo',
                'titles' => $titles,
                'iiprop' => 'url|mime|size|sha1|extmetadata',
                'format' => 'json',
                'formatversion' => 2,
            ]);

            if (! $response->successful()) {
                continue;
            }

            $payload = $response->json();
            $pages = is_array($payload['query']['pages'] ?? null) ? $payload['query']['pages'] : [];

            foreach ($pages as $page) {
                if (! is_array($page) || ! empty($page['missing'])) {
                    continue;
                }

                $title = (string) ($page['title'] ?? '');
                $file = preg_replace('/^File:/i', '', $title) ?? $title;
                $info = $page['imageinfo'][0] ?? null;
                if (! is_array($info) || empty($info['url'])) {
                    continue;
                }

                $metadata = [];
                foreach ((array) ($info['extmetadata'] ?? []) as $key => $value) {
                    if (is_array($value) && array_key_exists('value', $value)) {
                        $metadata[$key] = $this->cleanHtmlText((string) $value['value']);
                    }
                }

                $result[$file] = [
                    'file' => $file,
                    'url' => (string) $info['url'],
                    'description_url' => (string) ($info['descriptionurl'] ?? ''),
                    'mime' => (string) ($info['mime'] ?? ''),
                    'width' => isset($info['width']) ? (int) $info['width'] : null,
                    'height' => isset($info['height']) ? (int) $info['height'] : null,
                    'size' => isset($info['size']) ? (int) $info['size'] : null,
                    'sha1' => (string) ($info['sha1'] ?? ''),
                    'license' => $metadata['LicenseShortName'] ?? $metadata['UsageTerms'] ?? null,
                    'artist' => $metadata['Artist'] ?? null,
                    'credit' => $metadata['Credit'] ?? null,
                    'copyrighted' => $metadata['Copyrighted'] ?? null,
                ];
            }
        }

        return $result;
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

        if (! $values && preg_match_all('/^\s*\*\s*([^\n—-]+?)\s*(?:—|-)\s*(?:\d+|Scarce)/mi', $section, $matches)) {
            foreach ($matches[1] as $match) {
                $value = $this->cleanWikiText($match);
                if ($value) {
                    $values[] = $value;
                }
            }
        }

        return array_values(array_unique($values));
    }

    private function skinRows(string $wikitext, string $itemType, string $html = ''): array
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

        $rows = [];
        foreach ($this->extractAllTemplates($wikitext, $templateName) as $template) {
            $params = $this->parseTemplateParameters($template);
            $title = $this->cleanWikiText($this->param($params, ['Title', 'Name']));
            if (! $title) {
                continue;
            }

            $rows[] = [
                'name' => $title,
                'rarity' => $this->cleanWikiText($this->param($params, ['Rarity', 'Tier', 'Quality'])),
                'price' => $this->number($this->param($params, ['Price', 'Cost'])),
                'source' => $this->cleanWikiText($this->param($params, ['Source', 'Acquisition', 'Availability', 'Unlock'])),
                'update' => $this->cleanWikiText($this->param($params, ['Update', 'Added', 'Release'])),
                'image_file' => $this->fileNameFromParam(
                    $this->param($params, ['image', 'Image', 'Model', 'model', 'Weapon Image', 'Skin Image'])
                ),
            ];
        }

        if ($html !== '' && $rows) {
            $htmlRows = $this->skinMetadataFromHtml($html);
            foreach ($rows as &$row) {
                $key = $this->normalizeSearch((string) $row['name']);
                if ($key === '' || ! isset($htmlRows[$key])) {
                    continue;
                }

                foreach (['rarity', 'source', 'update'] as $field) {
                    if (empty($row[$field]) && ! empty($htmlRows[$key][$field])) {
                        $row[$field] = $htmlRows[$key][$field];
                    }
                }
                if (($row['price'] ?? null) === null && isset($htmlRows[$key]['price'])) {
                    $row['price'] = $htmlRows[$key]['price'];
                }
            }
            unset($row);
        }

        return $rows;
    }

    private function skinMetadataFromHtml(string $html): array
    {
        if (! class_exists(DOMDocument::class)) {
            return [];
        }

        $previous = libxml_use_internal_errors(true);
        $dom = new DOMDocument;
        $loaded = $dom->loadHTML('<?xml encoding="utf-8" ?>'.$html, LIBXML_NOWARNING | LIBXML_NOERROR);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (! $loaded) {
            return [];
        }

        $xpath = new DOMXPath($dom);
        $tables = $xpath->query('//table');
        $result = [];

        foreach ($tables as $table) {
            if (! $table instanceof DOMElement) {
                continue;
            }

            $headers = [];
            $headerNodes = $xpath->query('.//tr[1]/*[self::th or self::td]', $table);
            foreach ($headerNodes as $index => $cell) {
                $headers[$index] = strtolower((string) $this->cleanDomText((string) $cell->textContent));
            }

            $hasSkinHeader = false;
            foreach ($headers as $header) {
                if (str_contains($header, 'skin') || str_contains($header, 'name')) {
                    $hasSkinHeader = true;
                    break;
                }
            }
            if (! $hasSkinHeader) {
                continue;
            }

            foreach ($xpath->query('.//tr[position()>1]', $table) as $row) {
                $cells = $xpath->query('./td|./th', $row);
                if ($cells->length < 2) {
                    continue;
                }

                $values = [];
                foreach ($cells as $index => $cell) {
                    $values[$headers[$index] ?? ('col'.$index)] = $this->cleanDomText((string) $cell->textContent);
                }

                $name = $values['skin'] ?? $values['name'] ?? $values['legendary'] ?? reset($values);
                $name = is_string($name) ? trim($name) : '';
                $key = $this->normalizeSearch($name);
                if ($key === '') {
                    continue;
                }

                $rarity = $values['rarity'] ?? $values['tier'] ?? null;
                $source = $values['source'] ?? $values['availability'] ?? $values['acquisition'] ?? null;
                $update = $values['update'] ?? $values['added'] ?? null;
                $priceRaw = $values['price'] ?? $values['cost'] ?? null;

                $result[$key] = [
                    'rarity' => $rarity,
                    'source' => $source,
                    'update' => $update,
                    'price' => $this->number($priceRaw),
                ];
            }
        }

        return $result;
    }

    private function param(array $params, array $aliases): mixed
    {
        $lookup = [];
        foreach ($params as $key => $value) {
            $lookup[strtolower(trim((string) $key))] = $value;
        }

        foreach ($aliases as $alias) {
            $key = strtolower(trim((string) $alias));
            if (array_key_exists($key, $lookup)) {
                return $lookup[$key];
            }
        }

        return null;
    }

    private function patchHistory(string $wikitext): array
    {
        $section = $this->section($wikitext, 'Update History');
        if ($section === null) {
            return [];
        }

        $history = [];

        if (preg_match_all('/^\|\s*([^\n|]+?)\s*\|\|\s*(.+?)\s*$/mi', $section, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $patch = $this->cleanWikiText($match[1]);
                $note = $this->cleanWikiText($match[2]);
                if ($patch || $note) {
                    $history[] = ['patch' => $patch, 'note' => $note];
                }
            }
        }

        return array_values($history);
    }

    private function patchHistoryFromHtml(string $html): array
    {
        if (! class_exists(DOMDocument::class)) {
            return [];
        }

        $previous = libxml_use_internal_errors(true);
        $dom = new DOMDocument;
        $loaded = $dom->loadHTML('<?xml encoding="utf-8" ?>'.$html, LIBXML_NOWARNING | LIBXML_NOERROR);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (! $loaded) {
            return [];
        }

        $xpath = new DOMXPath($dom);
        $heading = $xpath->query('//*[@id="Update_History"]')->item(0);
        if (! $heading instanceof DOMElement) {
            return [];
        }

        $container = $heading;
        while ($container->parentNode instanceof DOMElement && ! preg_match('/^H[1-6]$/i', $container->parentNode->tagName)) {
            $container = $container->parentNode;
        }
        if ($container->parentNode instanceof DOMElement && preg_match('/^H[1-6]$/i', $container->parentNode->tagName)) {
            $container = $container->parentNode;
        }

        $table = $container->nextSibling;
        while ($table && (! $table instanceof DOMElement || strtolower($table->tagName) !== 'table')) {
            $table = $table->nextSibling;
        }

        if (! $table instanceof DOMElement) {
            return [];
        }

        $rows = [];
        foreach ($xpath->query('.//tr', $table) as $row) {
            $cells = $xpath->query('./td', $row);
            if ($cells->length < 2) {
                continue;
            }

            $patch = $this->cleanDomText((string) $cells->item(0)?->textContent);
            $note = $this->cleanDomText((string) $cells->item(1)?->textContent);
            if ($patch || $note) {
                $rows[] = ['patch' => $patch, 'note' => $note];
            }
        }

        return $rows;
    }

    private function bestImageFile(?string $explicit, array $candidates, string $subject, string $mode, array $imageInfo = []): ?string
    {
        if ($explicit) {
            $explicit = $this->normalizeFileTitle($explicit);
            foreach ($candidates as $candidate) {
                if (strcasecmp($this->normalizeFileTitle((string) $candidate), $explicit) === 0) {
                    return $this->normalizeFileTitle((string) $candidate);
                }
            }

            return $explicit;
        }

        $subjectKey = $this->normalizeSearch($subject);
        if ($subjectKey === '') {
            return null;
        }

        $best = null;
        $bestScore = PHP_INT_MIN;

        foreach ($candidates as $candidate) {
            $file = $this->normalizeFileTitle((string) $candidate);
            $base = pathinfo($file, PATHINFO_FILENAME);
            $key = $this->normalizeSearch($base);

            if ($key === '' || ! str_contains($key, $subjectKey)) {
                continue;
            }

            $score = 20;
            $lower = strtolower($base);
            $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));

            if (str_contains($lower, 'model')) $score += 50;
            if (str_contains($lower, '3d')) $score += 45;
            if (str_contains($lower, 'weapon')) $score += 12;

            foreach (['promo', 'concept', 'graphics', 'wallpaper', 'banner', 'keyart', 'key art', 'skin set', 'showcase', 'store'] as $badToken) {
                if (str_contains($lower, $badToken)) $score -= 45;
            }

            if ($extension === 'png') $score += 18;
            elseif ($extension === 'webp') $score += 10;
            elseif (in_array($extension, ['jpg', 'jpeg'], true)) $score -= 8;

            $info = $imageInfo[$file] ?? null;
            if (is_array($info)) {
                $width = (int) ($info['width'] ?? 0);
                $height = (int) ($info['height'] ?? 0);
                if ($width > 0 && $height > 0) {
                    $ratio = $width / $height;
                    if ($mode === 'skin') {
                        if ($ratio >= 2.4) $score += 28;
                        elseif ($ratio >= 2.0) $score += 12;
                        elseif ($ratio < 1.8) $score -= 16;
                    } elseif ($mode === 'base' && $ratio >= 2.0) {
                        $score += 12;
                    }
                }
            }

            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $file;
            }
        }

        return $best;
    }

    private function fileNameFromParam(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $raw = trim((string) $value);
        if ($raw === '') {
            return null;
        }

        if (preg_match('/\[\[(?:File|Image):([^\]|]+)(?:\|[^\]]*)?\]\]/i', $raw, $match)) {
            return $this->normalizeFileTitle($match[1]);
        }

        $clean = $this->cleanWikiText($raw);
        if ($clean && preg_match('/\.(?:png|jpe?g|webp|avif)$/i', $clean)) {
            return $this->normalizeFileTitle($clean);
        }

        return null;
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
        return $this->extractAllTemplates($wikitext, $templateName)[0] ?? null;
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
        $text = preg_replace_callback('/\{\{\s*(?:Hunt Dollars|Blood Bonds)\s*\}\}/i', fn () => '', $text) ?? $text;
        $text = preg_replace('/\{\{[^{}]*\}\}/', '', $text) ?? $text;
        $text = preg_replace('/<[^>]+>/', '', $text) ?? $text;
        $text = str_replace(["'''", "''"], '', $text);
        $text = preg_replace('/\s+/', ' ', $text) ?? $text;

        return ($text = trim($text)) !== '' ? $text : null;
    }

    private function cleanHtmlText(string $value): ?string
    {
        $text = html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/', ' ', $text) ?? $text;

        return ($text = trim($text)) !== '' ? $text : null;
    }

    private function cleanDomText(string $value): ?string
    {
        $text = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
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

    private function normalizeFileTitle(string $value): string
    {
        return trim(preg_replace('/^(?:File|Image):/i', '', str_replace('_', ' ', $value)) ?? $value);
    }

    private function normalizeSearch(string $value): string
    {
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        $ascii = is_string($ascii) ? strtolower($ascii) : strtolower($value);

        return preg_replace('/[^a-z0-9]+/', '', $ascii) ?? '';
    }

    private function wikiPath(string $value): string
    {
        return str_replace(' ', '_', trim($value));
    }

    private function wikiPageUrl(string $pageTitle): string
    {
        return self::BASE_URL.'/wiki/'.str_replace('%2F', '/', rawurlencode($pageTitle));
    }

    private function http()
    {
        return Http::acceptJson()
            ->withHeaders([
                'User-Agent' => 'HNT.ROCKS Arsenal Wiki Sync/1.0 (+https://hnt.rocks)',
            ])
            ->connectTimeout(8)
            ->timeout(20)
            ->retry(2, 400, throw: false);
    }
}
