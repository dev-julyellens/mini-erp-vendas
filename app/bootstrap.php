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
