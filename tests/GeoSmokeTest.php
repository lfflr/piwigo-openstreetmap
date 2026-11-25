<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/Geo/Coordinates.php';
require_once __DIR__ . '/../src/Geo/AddressParser.php';

use Lfflr\PiwigoOpenStreetMap\Geo\Coordinates;
use Lfflr\PiwigoOpenStreetMap\Geo\AddressParser;

function check(bool $condition, string $message): void
{
    if (!$condition) {
        fwrite(STDERR, "FAIL: " . $message . PHP_EOL);
        exit(1);
    }
}

$coord = Coordinates::fromInput('0', '0');
check($coord->latitude() === 0.0 && $coord->longitude() === 0.0, 'zero coordinates are valid');
check($coord->cacheKey('de-DE') === '0.000:0.000:de-de', 'cache key');

foreach ([['91', '0'], ['0', '181'], ['NaN', '0'], ['1; DROP TABLE', '0']] as $invalid) {
    try {
        Coordinates::fromInput($invalid[0], $invalid[1]);
        check(false, 'invalid coordinates accepted');
    } catch (InvalidArgumentException $exception) {
        // Expected.
    }
}

check(AddressParser::parse('{"address":{"village":"L\u0027Aquila","country":"Italia"}}') === [
    'locality' => "L'Aquila",
    'country' => 'Italia'
], 'village and apostrophe');
check(AddressParser::parse('{"error":"Unable to geocode"}') === [], 'error payload');
check(AddressParser::parse('not json') === [], 'invalid JSON');
check(AddressParser::parse('{"address":{"city":"Bremen","town":"Other"}}')['locality'] === 'Bremen', 'city precedence');

echo "OK: GeoSmokeTest" . PHP_EOL;
