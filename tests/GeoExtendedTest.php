<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/Geo/Coordinates.php';
require_once __DIR__ . '/../src/Geo/AddressParser.php';

use Lfflr\PiwigoOpenStreetMap\Geo\Coordinates;
use Lfflr\PiwigoOpenStreetMap\Geo\AddressParser;

$assertions = 0;

function checkGeo(bool $condition, string $message): void
{
    global $assertions;
    ++$assertions;
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function rejectsCoordinates($latitude, $longitude): bool
{
    try {
        Coordinates::fromInput($latitude, $longitude);
        return false;
    } catch (InvalidArgumentException $exception) {
        return true;
    }
}

foreach ([[-90, -180], [90, 180], [0, 0], ['53.0793', '8.8017']] as [$lat, $lon]) {
    $point = Coordinates::fromInput($lat, $lon);
    checkGeo($point->latitude() === (float) $lat, 'Latitude boundary / conversion');
    checkGeo($point->longitude() === (float) $lon, 'Longitude boundary / conversion');
}

foreach ([[91, 0], [-91, 0], [0, 181], [0, -181], ['NaN', 0], ['INF', 0], ['1; DROP TABLE', 0], [null, 0], [[], 0], [true, 0], ['', '']] as [$lat, $lon]) {
    checkGeo(rejectsCoordinates($lat, $lon), 'Unexpectedly accepted invalid coordinates');
}

$point = Coordinates::fromInput(53.0793, 8.8017);
checkGeo($point->cacheKey('de-DE') === '53.079:8.802:de-de', 'Cache precision / language normalization');
checkGeo($point->cacheKey('en_US', 2) === '53.08:8.80:en-us', 'Alternate cache precision');

foreach (['', 'en;evil', 'en\nInjected'] as $badLanguage) {
    try {
        $point->cacheKey($badLanguage);
        checkGeo(false, 'Invalid language accepted');
    } catch (InvalidArgumentException $exception) {
        checkGeo(true, 'Invalid language rejected');
    }
}

foreach ([-1, 7] as $precision) {
    try {
        $point->cacheKey('de', $precision);
        checkGeo(false, 'Invalid precision accepted');
    } catch (InvalidArgumentException $exception) {
        checkGeo(true, 'Invalid precision rejected');
    }
}

checkGeo(AddressParser::parse('{"address":{"town":"L\u0027Aquila","country":"Italia"}}') === [
    'locality' => "L'Aquila",
    'country' => 'Italia',
], 'Unicode apostrophe must be preserved');
checkGeo(AddressParser::parse('{"address":{"city":"Bremen","town":"Other","village":"Another"}}')['locality'] === 'Bremen', 'City priority');
checkGeo(AddressParser::parse('{"address":{"hamlet":"Hamlet"}}')['locality'] === 'Hamlet', 'Hamlet fallback');
checkGeo(AddressParser::parse('{"address":{"municipality":"Municipality"}}')['locality'] === 'Municipality', 'Municipality fallback');
checkGeo(AddressParser::parse('{"error":"Too many requests"}') === [], 'Provider error without address');
checkGeo(AddressParser::parse('invalid json') === [], 'Invalid JSON');
checkGeo(AddressParser::parse('{"address":null}') === [], 'Null address');
checkGeo(AddressParser::parse('{"address":{"city":123,"country":false}}') === [], 'Non-string fields ignored');
checkGeo(AddressParser::parse('{"address":{"city":" ","country":" Germany "}}') === ['country' => 'Germany'], 'Whitespace filtering');

echo 'OK: GeoExtendedTest (' . $assertions . ' assertions)' . PHP_EOL;
