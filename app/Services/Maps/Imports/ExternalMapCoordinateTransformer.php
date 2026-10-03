<?php

namespace App\Services\Maps\Imports;

final class ExternalMapCoordinateTransformer
{
    public const SOURCE_SIZE = 4096;
    public const HNT_SIZE = 2048;

    /**
     * Kamille's Leaflet CRS.Simple coordinates are [latitude/vertical, longitude/horizontal].
     * Matching tower POIs on all four maps verify the axes, direction and scale.
     *
     * @param array{0: int|float, 1: int|float} $coordinate
     * @return array{x: float, y: float}
     */
    public function transform(array $coordinate, int $hntWidth, int $hntHeight): array
    {
        if ($hntWidth !== self::HNT_SIZE || $hntHeight !== self::HNT_SIZE
            || count($coordinate) !== 2
            || ! is_numeric($coordinate[0]) || ! is_numeric($coordinate[1])
            || $coordinate[0] < 0 || $coordinate[0] > self::SOURCE_SIZE
            || $coordinate[1] < 0 || $coordinate[1] > self::SOURCE_SIZE) {
            throw new SourceFormatException('Unexpected map dimensions or coordinates.');
        }

        return [
            'x' => round((float) $coordinate[1] * self::HNT_SIZE / self::SOURCE_SIZE, 6),
            'y' => round((float) $coordinate[0] * self::HNT_SIZE / self::SOURCE_SIZE, 6),
        ];
    }
}
