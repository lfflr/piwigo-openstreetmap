<?php
declare(strict_types=1);

namespace Lfflr\PiwigoOpenStreetMap\Geo;

use InvalidArgumentException;

/**
 * Validated geographic coordinates. This class has no Piwigo dependencies.
 */
final class Coordinates
{
    private float $latitude;
    private float $longitude;

    private function __construct(float $latitude, float $longitude)
    {
        $this->latitude = $latitude;
        $this->longitude = $longitude;
    }

    public static function fromInput($latitude, $longitude): self
    {
        if (!self::isFiniteNumber($latitude) || !self::isFiniteNumber($longitude)) {
            throw new InvalidArgumentException('Latitude and longitude must be finite numbers.');
        }

        $lat = (float) $latitude;
        $lon = (float) $longitude;

        if ($lat < -90.0 || $lat > 90.0 || $lon < -180.0 || $lon > 180.0) {
            throw new InvalidArgumentException('Coordinates are outside the valid range.');
        }

        return new self($lat, $lon);
    }

    public function latitude(): float
    {
        return $this->latitude;
    }

    public function longitude(): float
    {
        return $this->longitude;
    }

    public function cacheKey(string $language, int $precision = 3): string
    {
        if ($precision < 0 || $precision > 6 || !preg_match('/^[A-Za-z]{2,8}(?:[-_][A-Za-z0-9]{2,8})*$/D', $language)) {
            throw new InvalidArgumentException('Invalid language or precision.');
        }

        return sprintf('%.*f:%.*f:%s', $precision, $this->latitude, $precision, $this->longitude, strtolower(str_replace('_', '-', $language)));
    }

    private static function isFiniteNumber($value): bool
    {
        if (!is_int($value) && !is_float($value) && !is_string($value)) {
            return false;
        }
        if (!is_numeric($value)) {
            return false;
        }
        return is_finite((float) $value);
    }
}
