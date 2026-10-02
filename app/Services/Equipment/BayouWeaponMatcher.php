<?php

namespace App\Services\Equipment;

use App\Models\EquipmentItem;
use Illuminate\Support\Collection;

class BayouWeaponMatcher
{
    public function __construct(private readonly array $aliases = []) {}

    /** @param Collection<int, EquipmentItem> $items */
    public function match(array $source, Collection $items): array
    {
        $weapons = $items->filter(fn (EquipmentItem $item) =>
            $item->item_type === 'weapon' && $item->source_status === 'active');
        $slug = (string) ($source['source_page_slug'] ?? '');
        $knownSlug = $this->aliases[$slug] ?? $slug;
        $bySlug = $weapons->filter(fn (EquipmentItem $item) => $item->slug === $knownSlug);
        if ($bySlug->count() === 1) {
            if (! isset($this->aliases[$slug]) &&
                $this->normalize((string) ($source['name'] ?? '')) !== $this->normalize($bySlug->first()->name)) {
                return $this->review('Known slug has a conflicting visible weapon name.');
            }
            return $this->result($bySlug->first(), 100, 'known_slug');
        }
        if ($bySlug->count() > 1) return $this->review('Multiple items share the known slug.');

        $name = $this->normalize((string) ($source['name'] ?? ''));
        if ($name !== '') {
            $byName = $weapons->filter(fn (EquipmentItem $item) => $this->normalize($item->name) === $name);
            if ($byName->count() === 1) return $this->result($byName->first(), 95, 'exact_name');
            if ($byName->count() > 1) return $this->review('Multiple items share the normalized name.');
        }

        $family = $this->normalize((string) ($source['family'] ?? ''));
        $variant = $this->normalize((string) ($source['variant'] ?? ''));
        if ($family !== '' && $variant !== '') {
            $byFamilyVariant = $weapons->filter(function (EquipmentItem $item) use ($family, $variant): bool {
                $familyName = $this->normalize((string) $item->family?->name);
                if ($familyName !== $family) return false;
                $itemName = $this->normalize($item->name);
                return $itemName === $family.' '.$variant;
            });
            if ($byFamilyVariant->count() === 1) return $this->result($byFamilyVariant->first(), 90, 'family_variant');
            if ($byFamilyVariant->count() > 1) return $this->review('Multiple items share the family and variant.');
        }

        return $this->review('No unique exact slug, name, or family/variant match.');
    }

    private function result(EquipmentItem $item, int $confidence, string $method): array
    {
        return ['item' => $item, 'action' => 'MATCHED', 'confidence' => $confidence,
            'method' => $method, 'reason' => null];
    }

    private function review(string $reason): array
    {
        return ['item' => null, 'action' => 'REVIEW_REQUIRED', 'confidence' => 0,
            'method' => null, 'reason' => $reason];
    }

    private function normalize(string $value): string
    {
        $value = function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value);
        return trim((string) preg_replace('/\s+/u', ' ', (string) preg_replace('/[^\pL\pN]+/u', ' ', $value)));
    }
}
