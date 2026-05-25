<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Router;
use App\Helpers\PathHelper;
use App\Middleware\AuthMiddleware;
use App\Middleware\PermissionMiddleware;

$router = new Router();

$router->get('/login', \App\Controllers\AuthController::class . '@showLogin');
$router->post('/login', \App\Controllers\AuthController::class . '@login');
$router->post('/logout', \App\Controllers\AuthController::class . '@logout');
$router->get('/forgot-password', \App\Controllers\AuthController::class . '@showForgotPassword');
$router->post('/forgot-password', \App\Controllers\AuthController::class . '@forgotPassword');
$router->get('/reset-password', \App\Controllers\AuthController::class . '@showResetPassword');
$router->post('/reset-password', \App\Controllers\AuthController::class . '@resetPassword');

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

$router->get('/audit-logs', \App\Controllers\AuditLogController::class . '@index');

$router->get('/stock-movements', \App\Controllers\StockMovementController::class . '@index');
$router->get('/stock-movements/create', \App\Controllers\StockMovementController::class . '@create');
$router->post('/stock-movements/store', \App\Controllers\StockMovementController::class . '@store');

$router->get('/api/products', \App\Controllers\ApiProductController::class . '@index');
$router->get('/api/orders', \App\Controllers\ApiOrderController::class . '@index');
$router->post('/api/orders', \App\Controllers\ApiOrderController::class . '@store');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = PathHelper::requestPath();

AuthMiddleware::handle($method, $path);
PermissionMiddleware::handle($method, $path);

$router->dispatch($method, $path);
