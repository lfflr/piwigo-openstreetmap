<?php
declare(strict_types=1);

namespace Lfflr\PiwigoOpenStreetMap\Geo;

use InvalidArgumentException;

/**
 * Validated data only. Calling code MUST separately verify authentication,
 * admin authorization, CSRF/API credentials and image access before any write.
 */
final class GeoWriteInput
{
    private int $imageId;
    private string $operation;
    private ?Coordinates $coordinates;

    private function __construct(int $imageId, string $operation, ?Coordinates $coordinates)
    {
        $this->imageId = $imageId;
        $this->operation = $operation;
        $this->coordinates = $coordinates;
    }

    public static function fromPayload(array $payload): self
    {
        foreach (array_keys($payload) as $key) {
            if (!in_array($key, ['image_id', 'operation', 'latitude', 'longitude'], true)) {
                throw new InvalidArgumentException('Unexpected parameter.');
            }
        }

        $id = $payload['image_id'] ?? null;
        if (!is_int($id) && !is_string($id)) {
            throw new InvalidArgumentException('Invalid image ID.');
        }
        if (!preg_match('/^[1-9][0-9]*$/D', (string) $id)) {
            throw new InvalidArgumentException('Invalid image ID.');
        }
        $imageId = filter_var((string) $id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($imageId === false) {
            throw new InvalidArgumentException('Image ID is out of range.');
        }

        $operation = $payload['operation'] ?? null;
        if ($operation === 'delete') {
            if (array_key_exists('latitude', $payload) || array_key_exists('longitude', $payload)) {
                throw new InvalidArgumentException('Delete does not accept coordinates.');
            }
            return new self($imageId, 'delete', null);
        }

        if ($operation !== 'set') {
            throw new InvalidArgumentException('Unknown operation.');
        }
        if (!array_key_exists('latitude', $payload) || !array_key_exists('longitude', $payload)) {
            throw new InvalidArgumentException('Both coordinates are required.');
        }

        return new self($imageId, 'set', Coordinates::fromInput($payload['latitude'], $payload['longitude']));
    }

    public function imageId(): int
    {
        return $this->imageId;
    }

    public function operation(): string
    {
        return $this->operation;
    }

    public function coordinates(): ?Coordinates
    {
        return $this->coordinates;
    }
}
