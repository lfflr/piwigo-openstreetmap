<?php
declare(strict_types=1);

// Draft integration adapter. NOT registered from main.inc.php until integration tests pass.
if (!defined('PHPWG_ROOT_PATH')) {
    return;
}

require_once dirname(__DIR__) . '/src/Geo/Coordinates.php';
require_once dirname(__DIR__) . '/src/Geo/GeoWriteInput.php';

use Lfflr\PiwigoOpenStreetMap\Geo\GeoWriteInput;

/**
 * Register only after browser-session authentication/CSRF tests on Piwigo 16/17.
 * API keys are deliberately NOT supported by this method.
 */
function osm_register_geo_write_method($service): void
{
    $service->addMethod(
        'osm.geo.setCoordinates',
        'osm_ws_geo_write',
        [
            'image_id' => ['type' => WS_TYPE_ID],
            'operation' => [],
            'latitude' => ['default' => null],
            'longitude' => ['default' => null],
            'pwg_token' => [],
        ],
        'Admin-only, session-authenticated update or removal of photo GPS coordinates.',
        '',
        ['admin_only' => true, 'post_only' => true]
    );
}

/**
 * Piwigo callback: function(array $params, PwgServer $service).
 *
 * @return array<string, int|string>|PwgError
 */
function osm_ws_geo_write($params, $service)
{
    // Do not inadvertently treat an API key as proof of CSRF protection.
    if (
        defined('PWG_API_KEY_REQUEST')
        || (isset($_SESSION['connected_with']) && 'ws_session_login_api_key' === $_SESSION['connected_with'])
    ) {
        return new PwgError(403, 'This operation requires an administrator browser session');
    }

    if (!is_admin()) {
        return new PwgError(403, 'Administrator permissions required');
    }

    if (
        !isset($params['pwg_token'])
        || !is_string($params['pwg_token'])
        || $params['pwg_token'] === ''
        || !hash_equals(get_pwg_token(), $params['pwg_token'])
    ) {
        return new PwgError(403, 'Invalid or missing security token');
    }

    // Piwigo supplies defaults for optional WS parameters, even for delete.
    $payload = [
        'image_id' => $params['image_id'] ?? null,
        'operation' => $params['operation'] ?? null,
    ];
    if (array_key_exists('latitude', $params) && $params['latitude'] !== null) {
        $payload['latitude'] = $params['latitude'];
    }
    if (array_key_exists('longitude', $params) && $params['longitude'] !== null) {
        $payload['longitude'] = $params['longitude'];
    }

    try {
        $request = GeoWriteInput::fromPayload($payload);
    } catch (InvalidArgumentException $exception) {
        return new PwgError(400, 'Invalid coordinate request');
    }

    $id = $request->imageId();
    $result = pwg_query('SELECT id FROM '.IMAGES_TABLE.' WHERE id = '.$id.' LIMIT 1;');
    if (!$result || pwg_db_num_rows($result) !== 1) {
        return new PwgError(404, 'Image not found');
    }

    if ($request->operation() === 'delete') {
        $sqlCoordinates = 'latitude = NULL, longitude = NULL';
    } else {
        $coordinates = $request->coordinates();
        // Input is already strictly validated as finite floats; values are
        // formatted with a fixed decimal point, never interpolated from POST.
        $latitude = number_format($coordinates->latitude(), 8, '.', '');
        $longitude = number_format($coordinates->longitude(), 8, '.', '');
        $sqlCoordinates = 'latitude = '.$latitude.', longitude = '.$longitude;
    }

    $updated = pwg_query('UPDATE '.IMAGES_TABLE.' SET '.$sqlCoordinates.' WHERE id = '.$id.' LIMIT 1;');
    if ($updated === false) {
        return new PwgError(500, 'Could not save image coordinates');
    }

    return ['image_id' => $id, 'operation' => $request->operation(), 'updated' => 1];
}
