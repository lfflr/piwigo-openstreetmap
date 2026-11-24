<?php
declare(strict_types=1);

define('PHPWG_ROOT_PATH', __DIR__ . '/../');
define('IMAGES_TABLE', 'pwg_images');

class PwgError
{
    public int $code;
    public function __construct(int $code, string $message)
    {
        $this->code = $code;
    }
}
class FakePiwigoServer
{
    public array $methods = [];
    public function addMethod($name, $callback, $params, $description, $include, $options): void
    {
        $this->methods[$name] = ['callback' => $callback, 'params' => $params, 'options' => $options];
    }
}
$GLOBALS['is_admin'] = false;
$GLOBALS['queries'] = [];
$GLOBALS['image_exists'] = true;
$GLOBALS['fail_update'] = false;
function is_admin(): bool { return $GLOBALS['is_admin']; }
function get_pwg_token(): string { return 'test-token-only'; }
function pwg_query(string $query)
{
    $GLOBALS['queries'][] = $query;
    if (str_starts_with($query, 'UPDATE') && $GLOBALS['fail_update']) {
        return false;
    }
    return true;
}
function pwg_db_num_rows($result): int { return $GLOBALS['image_exists'] ? 1 : 0; }

require_once __DIR__ . '/../include/ws_geo_write.inc.php';

$server = new FakePiwigoServer();
osm_register_geo_write_method($server);
$registration = $server->methods['osm.geo.setCoordinates'] ?? null;
if ($registration === null || $registration['options'] !== ['admin_only' => true, 'post_only' => true]) {
    throw new RuntimeException('Method registration is not admin/post only.');
}

function checkStatus($response, int $code, string $context): void
{
    if (!$response instanceof PwgError || $response->code !== $code) {
        throw new RuntimeException($context);
    }
}
function resetRequests(): void { $GLOBALS['queries'] = []; }

$params = ['image_id' => '5', 'operation' => 'set', 'latitude' => '0', 'longitude' => '0', 'pwg_token' => 'test-token-only'];
checkStatus(osm_ws_geo_write($params, $server), 403, 'Guest must be forbidden');
if ($GLOBALS['queries'] !== []) { throw new RuntimeException('Guest reached database'); }

$GLOBALS['is_admin'] = true;
resetRequests();
checkStatus(osm_ws_geo_write(array_merge($params, ['pwg_token' => 'bad']), $server), 403, 'Invalid CSRF');
checkStatus(osm_ws_geo_write(array_merge($params, ['pwg_token' => null]), $server), 403, 'Missing CSRF');
if ($GLOBALS['queries'] !== []) { throw new RuntimeException('CSRF failure reached database'); }

resetRequests();
checkStatus(osm_ws_geo_write(array_merge($params, ['latitude' => '91']), $server), 400, 'Latitude out of range');
checkStatus(osm_ws_geo_write(array_merge($params, ['image_id' => '5 OR 1=1']), $server), 400, 'SQL-like ID rejected');
if ($GLOBALS['queries'] !== []) { throw new RuntimeException('Invalid input reached database'); }

$GLOBALS['image_exists'] = false;
resetRequests();
checkStatus(osm_ws_geo_write($params, $server), 404, 'Missing image');
if (count($GLOBALS['queries']) !== 1) { throw new RuntimeException('Missing image was updated'); }

$GLOBALS['image_exists'] = true;
resetRequests();
$success = osm_ws_geo_write($params, $server);
if (!is_array($success) || $success['updated'] !== 1 || count($GLOBALS['queries']) !== 2
    || !str_contains($GLOBALS['queries'][1], 'latitude = 0.00000000, longitude = 0.00000000')) {
    throw new RuntimeException('Zero coordinate update');
}

resetRequests();
$deleted = osm_ws_geo_write(['image_id' => 5, 'operation' => 'delete', 'pwg_token' => 'test-token-only', 'latitude' => null, 'longitude' => null], $server);
if (!is_array($deleted) || !str_contains($GLOBALS['queries'][1], 'latitude = NULL, longitude = NULL')) {
    throw new RuntimeException('Delete must use NULL');
}
$GLOBALS['fail_update'] = true;
resetRequests();
checkStatus(osm_ws_geo_write($params, $server), 500, 'Database failure surfaced');
echo 'OK: GeoApiAdapterTest' . PHP_EOL;
