<?php

/**
 * Application configuration.
 * Values are read from environment (e.g. config/.env loaded in app/bootstrap.php).
 */
$dbHost = getenv('DB_HOST');
$dbPort = getenv('DB_PORT');
$dbName = getenv('DB_NAME');
$dbUser = getenv('DB_USER');
$dbPass = getenv('DB_PASSWORD');
$base = getenv('APP_BASE_URL');
$timezone = getenv('APP_TIMEZONE');

return [
    'app_name' => 'Mini ERP de Vendas',
    'base_url' => ($base !== false && $base !== '') ? $base : 'http://localhost/mini-erp-vendas/public',
    'timezone' => ($timezone !== false && $timezone !== '') ? $timezone : 'America/Sao_Paulo',
    'database' => [
        'host' => ($dbHost !== false && $dbHost !== '') ? $dbHost : '127.0.0.1',
        'port' => ($dbPort !== false && $dbPort !== '') ? $dbPort : '5432',
        'database' => ($dbName !== false && $dbName !== '') ? $dbName : 'mini_erp_vendas',
        'username' => ($dbUser !== false && $dbUser !== '') ? $dbUser : 'postgres',
        'password' => ($dbPass !== false && $dbPass !== '') ? $dbPass : 'postgres',
    ],
];
