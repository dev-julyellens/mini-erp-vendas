<?php

declare(strict_types=1);

/**
 * Compara rotas registradas em public/index.php com RoutePermissionMap::MAP.
 * Uso: php bin/check-route-permission-map.php
 */

$root = dirname(__DIR__);
$index = file_get_contents($root . '/public/index.php');

preg_match_all('/\$router->(get|post)\(\s*[\'"]([^\'"]+)[\'"]/i', $index, $m, PREG_SET_ORDER);
$routes = [];
foreach ($m as $r)
{
    $method = strtoupper($r[1]);
    $path = $r[2];
    if ($path !== '' && $path[0] !== '/')
    {
        $path = '/' . $path;
    }
    if ($path === '')
    {
        $path = '/';
    }
    $routes[$method . ' ' . $path] = true;
}

require $root . '/app/bootstrap.php';

$ref = new ReflectionClass(\App\Services\RoutePermissionMap::class);
$mapConst = $ref->getConstant('MAP');
$map = is_array($mapConst) ? $mapConst : [];

$skip = [
    'GET /login',
    'POST /login',
    'GET /forgot-password',
    'POST /forgot-password',
    'GET /reset-password',
    'POST /reset-password',
    'POST /logout',
    'GET /',
    'GET /notifications',
    'POST /notifications/open',
    'POST /notifications/read',
    'POST /notifications/read-all',
    'GET /lgpd/consent',
    'POST /lgpd/consent',
    'GET /select-company',
    'POST /select-company',
    'GET /onboarding',
    'POST /onboarding/company',
    'GET /onboarding/plan',
    'POST /onboarding/plan',
    'GET /subscription',
    'POST /subscription/pay',
    'POST /subscription/change-plan',
    'GET /profile',
    'POST /profile/update',
    'GET /profile/password',
    'POST /profile/password',
];
$publicAuth = ['POST /api/auth/login'];

$missingMap = [];
$intentionalOpen = [];
foreach (array_keys($routes) as $key)
{
    if (isset($map[$key]))
    {
        continue;
    }
    if (in_array($key, $skip, true) || in_array($key, $publicAuth, true))
    {
        continue;
    }
    if (str_contains($key, '/webhooks/'))
    {
        continue;
    }
    if (str_starts_with($key, 'GET /profile/') || str_starts_with($key, 'POST /profile/'))
    {
        $intentionalOpen[] = $key;

        continue;
    }
    if ($key === 'GET /reports')
    {
        $intentionalOpen[] = $key . ' (ACL OR em PermissionService::canAccessReportsHub)';

        continue;
    }
    $missingMap[] = $key;
}

$orphanMap = [];
foreach (array_keys($map) as $key)
{
    if (!isset($routes[$key]))
    {
        $orphanMap[] = $key;
    }
}

$indexByPath = [];
foreach (array_keys($routes) as $routeKey)
{
    [$method, $pathOnly] = explode(' ', $routeKey, 2);
    $indexByPath[$pathOnly][] = $method;
}
$mapByPath = [];
foreach (array_keys($map) as $mapKey)
{
    [$method, $pathOnly] = explode(' ', $mapKey, 2);
    $mapByPath[$pathOnly][] = $method;
}
$methodMismatch = [];
foreach ($mapByPath as $pathOnly => $mapMethods)
{
    if (!isset($indexByPath[$pathOnly]))
    {
        continue;
    }
    sort($mapMethods);
    $indexMethods = $indexByPath[$pathOnly];
    sort($indexMethods);
    if ($mapMethods !== $indexMethods)
    {
        $methodMismatch[] = sprintf(
            '%s — index: [%s] map: [%s]',
            $pathOnly,
            implode(', ', $indexMethods),
            implode(', ', $mapMethods)
        );
    }
}

echo 'Rotas em index.php: ' . count($routes) . PHP_EOL;
echo 'Entradas no MAP: ' . count($map) . PHP_EOL . PHP_EOL;

$ok = $missingMap === [] && $orphanMap === [] && $methodMismatch === [];

if ($missingMap !== [])
{
    echo "FALHA — rotas protegidas sem MAP:\n";
    foreach ($missingMap as $k)
    {
        echo "  - {$k}\n";
    }
    echo PHP_EOL;
}

if ($orphanMap !== [])
{
    echo "FALHA — entradas MAP sem rota em index.php:\n";
    foreach ($orphanMap as $k)
    {
        echo "  - {$k}\n";
    }
    echo PHP_EOL;
}

if ($methodMismatch !== [])
{
    echo "FALHA — mesmo path, método HTTP diferente:\n";
    foreach ($methodMismatch as $line)
    {
        echo "  - {$line}\n";
    }
    echo PHP_EOL;
}

if ($intentionalOpen !== [])
{
    echo "Info — rotas sem MAP (comportamento esperado):\n";
    foreach ($intentionalOpen as $k)
    {
        echo "  - {$k}\n";
    }
    echo PHP_EOL;
}

exit($ok ? 0 : 1);
