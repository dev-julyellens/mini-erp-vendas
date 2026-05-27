<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Mapeamento rota HTTP → módulo + ação ACL.
 */
final class RoutePermissionMap
{
    /** @var array<string, array{0: string, 1: string}> */
    private const MAP = [
        'GET /customers' => ['clientes', 'visualizar'],
        'GET /customers/create' => ['clientes', 'criar'],
        'POST /customers/store' => ['clientes', 'criar'],
        'GET /customers/edit' => ['clientes', 'editar'],
        'POST /customers/update' => ['clientes', 'editar'],
        'POST /customers/delete' => ['clientes', 'excluir'],

        'GET /products' => ['produtos', 'visualizar'],
        'GET /products/create' => ['produtos', 'criar'],
        'POST /products/store' => ['produtos', 'criar'],
        'GET /products/edit' => ['produtos', 'editar'],
        'POST /products/update' => ['produtos', 'editar'],
        'POST /products/delete' => ['produtos', 'excluir'],

        'GET /services' => ['produtos', 'visualizar'],
        'GET /services/create' => ['produtos', 'criar'],
        'POST /services/store' => ['produtos', 'criar'],
        'GET /services/edit' => ['produtos', 'editar'],
        'POST /services/update' => ['produtos', 'editar'],
        'POST /services/delete' => ['produtos', 'excluir'],

        'GET /categories' => ['produtos', 'visualizar'],
        'GET /categories/create' => ['produtos', 'criar'],
        'POST /categories/store' => ['produtos', 'criar'],
        'GET /categories/edit' => ['produtos', 'editar'],
        'POST /categories/update' => ['produtos', 'editar'],
        'POST /categories/delete' => ['produtos', 'excluir'],

        'GET /orders' => ['vendas', 'visualizar'],
        'GET /orders/show' => ['vendas', 'visualizar'],
        'GET /orders/create' => ['vendas', 'criar'],
        'POST /orders/store' => ['vendas', 'criar'],
        'POST /orders/cancel' => ['vendas', 'excluir'],

        'GET /quotes' => ['vendas', 'visualizar'],
        'GET /quotes/show' => ['vendas', 'visualizar'],
        'GET /quotes/create' => ['vendas', 'criar'],
        'POST /quotes/store' => ['vendas', 'criar'],
        'POST /quotes/convert' => ['vendas', 'criar'],
        'POST /quotes/cancel' => ['vendas', 'excluir'],
        'POST /quotes/mark-sent' => ['vendas', 'editar'],

        'GET /inventory' => ['estoque', 'visualizar'],
        'GET /inventory/show' => ['estoque', 'visualizar'],
        'POST /inventory/start' => ['estoque', 'criar'],
        'POST /inventory/save-lines' => ['estoque', 'editar'],
        'POST /inventory/finalize' => ['estoque', 'criar'],
        'POST /inventory/cancel' => ['estoque', 'excluir'],

        'GET /api/products' => ['produtos', 'visualizar'],
        'GET /api/orders' => ['vendas', 'visualizar'],
        'POST /api/orders' => ['vendas', 'criar'],
        'POST /api/orders/cancel' => ['vendas', 'excluir'],
        'POST /api/quotes' => ['vendas', 'criar'],

        'GET /reports/sales-period' => ['vendas', 'visualizar'],
        'GET /reports/sales-period/export' => ['vendas', 'visualizar'],
        'GET /reports/sales-customer' => ['vendas', 'visualizar'],
        'GET /reports/sales-customer/export' => ['vendas', 'visualizar'],
        'GET /reports/sales-product' => ['vendas', 'visualizar'],
        'GET /reports/sales-product/export' => ['vendas', 'visualizar'],
        'GET /reports/top-products' => ['vendas', 'visualizar'],
        'GET /reports/top-products/export' => ['vendas', 'visualizar'],

        'GET /reports/low-stock' => ['estoque', 'visualizar'],
        'GET /reports/low-stock/export' => ['estoque', 'visualizar'],

        'GET /reports/cash-flow' => ['financeiro', 'visualizar'],
        'GET /reports/cash-flow/export' => ['financeiro', 'visualizar'],

        'GET /admin/companies' => ['usuarios', 'visualizar'],
        'GET /admin/companies/create' => ['usuarios', 'criar'],
        'POST /admin/companies/store' => ['usuarios', 'criar'],
        'GET /admin/companies/edit' => ['usuarios', 'editar'],
        'POST /admin/companies/update' => ['usuarios', 'editar'],
        'POST /admin/companies/toggle-active' => ['usuarios', 'editar'],

        'GET /admin/users' => ['usuarios', 'visualizar'],
        'GET /admin/users/create' => ['usuarios', 'criar'],
        'POST /admin/users/store' => ['usuarios', 'criar'],
        'GET /admin/users/edit' => ['usuarios', 'editar'],
        'POST /admin/users/update' => ['usuarios', 'editar'],
        'POST /admin/users/toggle-active' => ['usuarios', 'editar'],
        'GET /admin/users/reset-password' => ['usuarios', 'editar'],
        'POST /admin/users/reset-password' => ['usuarios', 'editar'],

        'GET /user-companies' => ['usuarios', 'visualizar'],
        'POST /user-companies/attach' => ['usuarios', 'criar'],
        'POST /user-companies/update-role' => ['usuarios', 'editar'],
        'POST /user-companies/toggle-active' => ['usuarios', 'editar'],
        'POST /user-companies/detach' => ['usuarios', 'excluir'],

        'GET /admin/saas' => ['usuarios', 'visualizar'],
        'GET /admin/saas/subscriptions' => ['usuarios', 'visualizar'],
        'POST /admin/saas/assign-plan' => ['usuarios', 'editar'],

        'GET /audit-logs' => ['usuarios', 'visualizar'],
        'GET /access-logs' => ['usuarios', 'visualizar'],

        'GET /backups' => ['usuarios', 'visualizar'],
        'POST /backups/create' => ['usuarios', 'criar'],
        'GET /backups/download' => ['usuarios', 'criar'],
        'POST /backups/restore' => ['usuarios', 'excluir'],
        'POST /backups/settings' => ['usuarios', 'editar'],
        'POST /backups/cleanup' => ['usuarios', 'editar'],

        'GET /finance' => ['financeiro', 'visualizar'],
        'GET /finance/cash-flow' => ['financeiro', 'visualizar'],
        'GET /finance/accounts-receivable' => ['financeiro', 'visualizar'],
        'GET /finance/accounts-receivable/show' => ['financeiro', 'visualizar'],
        'GET /finance/accounts-receivable/receive' => ['financeiro', 'criar'],
        'POST /finance/accounts-receivable/receive' => ['financeiro', 'criar'],

        'GET /finance/installments/overdue' => ['financeiro', 'visualizar'],
        'GET /finance/installments/open' => ['financeiro', 'visualizar'],
        'GET /finance/installments/history' => ['financeiro', 'visualizar'],
        'GET /finance/installments/pay' => ['financeiro', 'criar'],
        'POST /finance/installments/pay' => ['financeiro', 'criar'],

        'GET /finance/pix/charge' => ['financeiro', 'visualizar'],
        'GET /finance/pix/receipt' => ['financeiro', 'visualizar'],
        'GET /finance/pix/status' => ['financeiro', 'visualizar'],
        'POST /finance/pix/create-account' => ['financeiro', 'criar'],
        'POST /finance/pix/create-installment' => ['financeiro', 'criar'],
        'POST /finance/pix/simulate-pay' => ['financeiro', 'criar'],

        'GET /stock-movements' => ['estoque', 'visualizar'],
        'GET /stock-movements/create' => ['estoque', 'criar'],
        'POST /stock-movements/store' => ['estoque', 'criar'],
    ];

    /**
     * @return array{module: string, action: string}|null
     */
    public static function resolve(string $method, string $path): ?array
    {
        $key = $method . ' ' . $path;
        if (!isset(self::MAP[$key]))
        {
            return null;
        }

        [$module, $action] = self::MAP[$key];

        return ['module' => $module, 'action' => $action];
    }
}
