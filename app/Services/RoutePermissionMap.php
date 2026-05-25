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

        'GET /orders' => ['vendas', 'visualizar'],
        'GET /orders/show' => ['vendas', 'visualizar'],
        'GET /orders/create' => ['vendas', 'criar'],
        'POST /orders/store' => ['vendas', 'criar'],
        'POST /orders/cancel' => ['vendas', 'excluir'],

        'GET /api/products' => ['produtos', 'visualizar'],
        'GET /api/orders' => ['vendas', 'visualizar'],
        'POST /api/orders' => ['vendas', 'criar'],
        'POST /api/orders/cancel' => ['vendas', 'excluir'],

        'GET /audit-logs' => ['usuarios', 'visualizar'],

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
