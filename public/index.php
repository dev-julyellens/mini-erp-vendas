<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Router;
use App\Helpers\ApiRequest;
use App\Helpers\PathHelper;
use App\Middleware\ApiErrorHandlerMiddleware;
use App\Middleware\ApiMiddleware;
use App\Middleware\AuthMiddleware;
use App\Middleware\JwtAuthMiddleware;
use App\Middleware\PermissionMiddleware;

$router = new Router();

$router->get('/login', \App\Controllers\AuthController::class . '@showLogin');
$router->post('/login', \App\Controllers\AuthController::class . '@login');
$router->get('/select-company', \App\Controllers\AuthController::class . '@showSelectCompany');
$router->post('/select-company', \App\Controllers\AuthController::class . '@selectCompany');
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

$router->get('/services', \App\Controllers\ServiceController::class . '@index');
$router->get('/services/create', \App\Controllers\ServiceController::class . '@create');
$router->post('/services/store', \App\Controllers\ServiceController::class . '@store');
$router->get('/services/edit', \App\Controllers\ServiceController::class . '@edit');
$router->post('/services/update', \App\Controllers\ServiceController::class . '@update');
$router->post('/services/delete', \App\Controllers\ServiceController::class . '@delete');

$router->get('/categories', \App\Controllers\CategoryController::class . '@index');
$router->get('/categories/create', \App\Controllers\CategoryController::class . '@create');
$router->post('/categories/store', \App\Controllers\CategoryController::class . '@store');
$router->get('/categories/edit', \App\Controllers\CategoryController::class . '@edit');
$router->post('/categories/update', \App\Controllers\CategoryController::class . '@update');
$router->post('/categories/delete', \App\Controllers\CategoryController::class . '@delete');

$router->get('/orders', \App\Controllers\OrderController::class . '@index');
$router->get('/orders/show', \App\Controllers\OrderController::class . '@show');
$router->get('/orders/create', \App\Controllers\OrderController::class . '@create');
$router->post('/orders/store', \App\Controllers\OrderController::class . '@store');
$router->post('/orders/cancel', \App\Controllers\OrderController::class . '@cancel');

$router->get('/reports', \App\Controllers\ReportController::class . '@index');
$router->get('/reports/sales-period', \App\Controllers\ReportController::class . '@salesPeriod');
$router->get('/reports/sales-period/export', \App\Controllers\ReportController::class . '@exportSalesPeriod');
$router->get('/reports/sales-customer', \App\Controllers\ReportController::class . '@salesCustomer');
$router->get('/reports/sales-customer/export', \App\Controllers\ReportController::class . '@exportSalesCustomer');
$router->get('/reports/sales-product', \App\Controllers\ReportController::class . '@salesProduct');
$router->get('/reports/sales-product/export', \App\Controllers\ReportController::class . '@exportSalesProduct');
$router->get('/reports/top-products', \App\Controllers\ReportController::class . '@topProducts');
$router->get('/reports/top-products/export', \App\Controllers\ReportController::class . '@exportTopProducts');
$router->get('/reports/low-stock', \App\Controllers\ReportController::class . '@lowStock');
$router->get('/reports/low-stock/export', \App\Controllers\ReportController::class . '@exportLowStock');
$router->get('/reports/cash-flow', \App\Controllers\ReportController::class . '@cashFlow');
$router->get('/reports/cash-flow/export', \App\Controllers\ReportController::class . '@exportCashFlow');

$router->get('/audit-logs', \App\Controllers\AuditLogController::class . '@index');

$router->get('/backups', \App\Controllers\BackupController::class . '@index');
$router->post('/backups/create', \App\Controllers\BackupController::class . '@create');
$router->get('/backups/download', \App\Controllers\BackupController::class . '@download');
$router->post('/backups/restore', \App\Controllers\BackupController::class . '@restore');
$router->post('/backups/settings', \App\Controllers\BackupController::class . '@updateSchedule');
$router->post('/backups/cleanup', \App\Controllers\BackupController::class . '@cleanup');

$router->get('/finance', \App\Controllers\FinanceController::class . '@index');
$router->get('/finance/cash-flow', \App\Controllers\FinanceController::class . '@cashFlow');
$router->get('/finance/accounts-receivable', \App\Controllers\AccountsReceivableController::class . '@index');
$router->get('/finance/accounts-receivable/show', \App\Controllers\AccountsReceivableController::class . '@show');
$router->get('/finance/accounts-receivable/receive', \App\Controllers\AccountsReceivableController::class . '@receive');
$router->post('/finance/accounts-receivable/receive', \App\Controllers\AccountsReceivableController::class . '@storePayment');

$router->get('/finance/installments/overdue', \App\Controllers\InstallmentController::class . '@overdue');
$router->get('/finance/installments/open', \App\Controllers\InstallmentController::class . '@open');
$router->get('/finance/installments/history', \App\Controllers\InstallmentController::class . '@history');
$router->get('/finance/installments/pay', \App\Controllers\InstallmentController::class . '@pay');
$router->post('/finance/installments/pay', \App\Controllers\InstallmentController::class . '@storePayment');

$router->get('/stock-movements', \App\Controllers\StockMovementController::class . '@index');
$router->get('/stock-movements/create', \App\Controllers\StockMovementController::class . '@create');
$router->post('/stock-movements/store', \App\Controllers\StockMovementController::class . '@store');

$router->post('/api/auth/login', \App\Controllers\ApiAuthController::class . '@login');
$router->get('/api/products', \App\Controllers\ApiProductController::class . '@index');
$router->get('/api/orders', \App\Controllers\ApiOrderController::class . '@index');
$router->post('/api/orders', \App\Controllers\ApiOrderController::class . '@store');
$router->post('/api/orders/cancel', \App\Controllers\ApiOrderController::class . '@cancel');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = PathHelper::requestPath();

if (ApiRequest::isApiPath($path))
{
    ApiErrorHandlerMiddleware::register();
    ApiMiddleware::handle($method, $path);
}

JwtAuthMiddleware::handle($method, $path);
AuthMiddleware::handle($method, $path);
PermissionMiddleware::handle($method, $path);

$router->dispatch($method, $path);
