<?php
namespace App\Services\Equipment;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class HuntifyEquipmentSource implements EquipmentSourceInterface
{
    public function key(): string { return 'huntify'; }
    public function name(): string { return 'Huntify HuntWiki'; }
    public function baseUrl(): string { return 'https://wiki.huntify.win'; }

    public function items(): iterable
    {
        $skins = [];
        foreach ($this->dataset('Skins') as $skin) {
            if (! empty($skin['id'])) $skins[$skin['id']] = $skin;
        }
        foreach (['Weapons' => 'weapon', 'Tools' => 'tool', 'Consumables' => 'consumable'] as $module => $type) {
            foreach ($this->dataset($module) as $row) {
                if (! is_array($row)) continue;
                $row['_item_type'] = $type;
                $row['_source_url'] = $this->baseUrl()."/Hunt/modules/$module/data.js";
                $row['_skins'] = array_values(array_filter(array_map(fn($id)=>$skins[$id] ?? null,$row['skinIds'] ?? [])));
                yield $row;
            }
        }
    }

    private function dataset(string $module): array
    {
        $url = $this->baseUrl()."/Hunt/modules/$module/data.js";
        $response = Http::timeout(20)->retry(2, 500)->get($url);
        if (! $response->successful()) throw new RuntimeException("Equipment feed unavailable: $url (HTTP {$response->status()})");
        $body = trim($response->body());
        if (strlen($body) > 5_000_000) throw new RuntimeException("Equipment feed too large: $url");
        $prefix = 'window.Hunt.data.register("'.strtolower($module).'", ';
        if (! str_starts_with($body, $prefix) || ! str_ends_with($body, ');')) throw new RuntimeException("Unexpected equipment feed format: $url");
        $rows = json_decode(substr($body, strlen($prefix), -2), true, 512, JSON_THROW_ON_ERROR);
        if (! is_array($rows) || ! array_is_list($rows)) throw new RuntimeException("Equipment feed is not a list: $url");
        return $rows;
    }
}
