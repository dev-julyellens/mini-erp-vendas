<?php

declare(strict_types=1);

ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');

if (
    (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== '' && $_SERVER['HTTPS'] !== 'off')
    || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
)
{
    ini_set('session.cookie_secure', '1');
}

session_name('mini_erp_session');
session_start();

$autoload = dirname(__DIR__) . '/vendor/autoload.php';
if (!is_file($autoload))
{
    die('Execute <code>composer install</code> na raiz do projeto.');
}

require $autoload;

ini_set('default_charset', 'UTF-8');
if (function_exists('mb_internal_encoding'))
{
    mb_internal_encoding('UTF-8');
    mb_http_output('UTF-8');
}

\App\Core\Env::load(dirname(__DIR__) . '/config/.env');

\App\Core\SecurityBootstrap::assertSafeConfiguration();

$config = require dirname(__DIR__) . '/config/app.php';
$tz = isset($config['timezone']) ? (string) $config['timezone'] : 'America/Sao_Paulo';
if (!@date_default_timezone_set($tz))
{
    date_default_timezone_set('America/Sao_Paulo');
}
