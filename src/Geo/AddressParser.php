<?php
declare(strict_types=1);

namespace Lfflr\PiwigoOpenStreetMap\Geo;

/**
 * Converts a Nominatim reverse response into a stable, optional set of tags.
 * Does not perform network calls or write to the Piwigo database.
 */
final class AddressParser
{
    private const LOCALITY_KEYS = ['city', 'town', 'village', 'hamlet', 'municipality'];

    /**
     * @return array<string, string>
     */
    public static function parse(string $json): array
    {
        try {
            $data = json_decode($json, true, 32, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            return [];
        }

        if (!is_array($data) || !isset($data['address']) || !is_array($data['address'])) {
            return [];
        }

        $address = $data['address'];
        $tags = [];
        foreach (self::LOCALITY_KEYS as $key) {
            if (isset($address[$key]) && is_string($address[$key]) && trim($address[$key]) !== '') {
                $tags['locality'] = trim($address[$key]);
                break;
            }
        }

        foreach (['suburb', 'city_district', 'county', 'state', 'country', 'postcode', 'country_code'] as $key) {
            if (isset($address[$key]) && is_string($address[$key]) && trim($address[$key]) !== '') {
                $tags[$key] = trim($address[$key]);
            }
        }

        return $tags;
    }
}
