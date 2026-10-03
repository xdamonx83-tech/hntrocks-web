<?php

namespace App\Services\Maps\Imports;

use InvalidArgumentException;

final class MapMarkerImportProviderRegistry
{
    /** @return array<string, MapMarkerImportProviderInterface> */
    public function all(): array
    {
        $providers = [app(KamilleMapMarkerProvider::class)];

        $indexed = [];

        foreach ($providers as $provider) {
            $indexed[$provider->id()] = $provider;
        }

        return $indexed;
    }

    public function get(string $id): MapMarkerImportProviderInterface
    {
        return $this->all()[$id] ?? throw new InvalidArgumentException('Unknown map marker provider.');
    }
}
