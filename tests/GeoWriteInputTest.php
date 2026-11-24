<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/Geo/Coordinates.php';
require_once __DIR__ . '/../src/Geo/GeoWriteInput.php';

use Lfflr\PiwigoOpenStreetMap\Geo\GeoWriteInput;

$checks = 0;
function verify(bool $condition, string $description): void
{
    global $checks;
    ++$checks;
    if (!$condition) {
        throw new RuntimeException($description);
    }
}
function rejected(array $data): bool
{
    try {
        GeoWriteInput::fromPayload($data);
        return false;
    } catch (InvalidArgumentException $exception) {
        return true;
    }
}

$set = GeoWriteInput::fromPayload([
    'image_id' => '12', 'operation' => 'set', 'latitude' => '0', 'longitude' => '0',
]);
verify($set->imageId() === 12, 'ID parsed');
verify($set->operation() === 'set', 'Set operation');
verify($set->coordinates() !== null && $set->coordinates()->latitude() === 0.0, 'Zero latitude is valid');
verify($set->coordinates() !== null && $set->coordinates()->longitude() === 0.0, 'Zero longitude is valid');

$delete = GeoWriteInput::fromPayload(['image_id' => 12, 'operation' => 'delete']);
verify($delete->operation() === 'delete' && $delete->coordinates() === null, 'Explicit delete');

$invalid = [
    ['image_id' => 0, 'operation' => 'delete'],
    ['image_id' => '-1', 'operation' => 'delete'],
    ['image_id' => '01', 'operation' => 'delete'],
    ['image_id' => '1 OR 1=1', 'operation' => 'delete'],
    ['image_id' => '9999999999999999999999999999', 'operation' => 'delete'],
    ['image_id' => true, 'operation' => 'delete'],
    ['image_id' => [], 'operation' => 'delete'],
    ['image_id' => 1, 'operation' => 'update'],
    ['image_id' => 1, 'operation' => 'set', 'latitude' => 5],
    ['image_id' => 1, 'operation' => 'set', 'latitude' => '', 'longitude' => 3],
    ['image_id' => 1, 'operation' => 'set', 'latitude' => 90.01, 'longitude' => 3],
    ['image_id' => 1, 'operation' => 'set', 'latitude' => 0, 'longitude' => -180.01],
    ['image_id' => 1, 'operation' => 'delete', 'latitude' => null],
    ['image_id' => 1, 'operation' => 'set', 'latitude' => 0, 'longitude' => 0, 'role' => 'admin'],
];
foreach ($invalid as $index => $payload) {
    verify(rejected($payload), 'Invalid payload #' . $index . ' must be rejected');
}

echo 'OK: GeoWriteInputTest (' . $checks . ' assertions)' . PHP_EOL;
