<?php

namespace App\Services\Maps\Imports;

interface MapMarkerImportProviderInterface
{
    public function id(): string;

    public function name(): string;

    /** @return array<string, array{id: int, name: string}> */
    public function maps(): array;

    /** @return array<string, array{source_category: string, type: string, subtype: ?string}> */
    public function categories(): array;

    /** @return array<int, array{source_key: string, source_category: string, type: string, subtype: ?string, x: float, y: float, label_de: ?string, label_en: ?string}> */
    public function markers(string $mapSlug): array;

    /** @return array<string, int> Source categories and counts skipped because coordinates are outside the map. */
    public function outOfBounds(string $mapSlug): array;

    /** @return array<int, string> */
    public function outOfBoundsKeys(string $mapSlug): array;
}
