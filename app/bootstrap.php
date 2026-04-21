<?php

declare(strict_types=1);

session_start();

$autoload = dirname(__DIR__) . '/vendor/autoload.php';
if (!is_file($autoload))
{
    die('Execute <code>composer install</code> na raiz do projeto.');
}

require $autoload;

\App\Core\Env::load(dirname(__DIR__) . '/config/.env');

$config = require dirname(__DIR__) . '/config/app.php';
$tz = isset($config['timezone']) ? (string) $config['timezone'] : 'America/Sao_Paulo';
if (!@date_default_timezone_set($tz)) {
    date_default_timezone_set('America/Sao_Paulo');
}
