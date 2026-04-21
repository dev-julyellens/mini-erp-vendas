<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Router;
use App\Helpers\PathHelper;

$router = new Router();

$router->get('/', \App\Controllers\DashboardController::class . '@index');

$router->get('/customers', \App\Controllers\CustomerController::class . '@index');
$router->get('/customers/create', \App\Controllers\CustomerController::class . '@create');
$router->post('/customers/store', \App\Controllers\CustomerController::class . '@store');
$router->get('/customers/edit', \App\Controllers\CustomerController::class . '@edit');
$router->post('/customers/update', \App\Controllers\CustomerController::class . '@update');
$router->post('/customers/delete', \App\Controllers\CustomerController::class . '@delete');

$router->get('/products', \App\Controllers\ProductController::class . '@index');
$router->get('/products/create', \App\Controllers\ProductController::class . '@create');
$router->post('/products/store', \App\Controllers\ProductController::class . '@store');
$router->get('/products/edit', \App\Controllers\ProductController::class . '@edit');
$router->post('/products/update', \App\Controllers\ProductController::class . '@update');
$router->post('/products/delete', \App\Controllers\ProductController::class . '@delete');

$router->get('/orders', \App\Controllers\OrderController::class . '@index');
$router->get('/orders/show', \App\Controllers\OrderController::class . '@show');
$router->get('/orders/create', \App\Controllers\OrderController::class . '@create');
$router->post('/orders/store', \App\Controllers\OrderController::class . '@store');

$router->get('/api/products', \App\Controllers\ApiProductController::class . '@index');
$router->get('/api/orders', \App\Controllers\ApiOrderController::class . '@index');
$router->post('/api/orders', \App\Controllers\ApiOrderController::class . '@store');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = PathHelper::requestPath();

$router->dispatch($method, $path);
