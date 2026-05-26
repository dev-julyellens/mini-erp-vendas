<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Services\PlatformAdminService;

/**
 * Estrutura de navegação lateral agrupada (UI).
 *
 * @phpstan-type NavItem array{
 *   label: string,
 *   href: string,
 *   icon: string,
 *   active: bool,
 *   badge?: int|string,
 *   external?: bool,
 *   children?: list<NavItem>
 * }
 * @phpstan-type NavGroup array{ id: string, label: string, items: list<NavItem> }
 */
final class NavigationMenu
{
    /**
     * @return list<NavGroup>
     */
    public static function groups(
        string $currentPath,
        callable $url,
        ?int $notificationUnreadCount = null
    ): array
    {
        $navActive = static fn(string $path): string => $currentPath === $path ? 'active' : '';
        $navPrefix = static function (string $prefix) use ($currentPath): string
        {
            $prefix = '/' . trim($prefix, '/');
            if ($prefix === '//')
            {
                $prefix = '/';
            }
            if ($prefix !== '/' && str_starts_with($currentPath, $prefix))
            {
                return 'active';
            }

            return '';
        };

        $canClientes = Permission::canView('clientes');
        $canProdutos = Permission::canView('produtos');
        $canVendas = Permission::canView('vendas');
        $canEstoque = Permission::canView('estoque');
        $canFinanceiro = Permission::canView('financeiro');
        $canUsuarios = Permission::canView('usuarios');
        $canRelatorios = $canVendas || $canEstoque || $canFinanceiro;
        $isPlatformAdmin = (new PlatformAdminService())->isPlatformAdmin();
        $canManageLinks = $isPlatformAdmin || Permission::can('usuarios', 'editar');

        $groups = [];

        $dashboardItems = [
            [
                'label' => 'Visão geral',
                'href' => $url(''),
                'icon' => 'bi-speedometer2',
                'active' => $navActive('/') !== '',
            ],
        ];
        if ($canVendas || $canFinanceiro || $canEstoque)
        {
            $dashboardItems[] = [
                'label' => 'Comercial',
                'href' => $url('') . '#dash-comercial',
                'icon' => 'bi-graph-up-arrow',
                'active' => false,
            ];
            $dashboardItems[] = [
                'label' => 'Financeiro',
                'href' => $url('') . '#dash-financeiro',
                'icon' => 'bi-cash-coin',
                'active' => false,
            ];
            $dashboardItems[] = [
                'label' => 'Operacional',
                'href' => $url('') . '#dash-operacional',
                'icon' => 'bi-boxes',
                'active' => false,
            ];
            $dashboardItems[] = [
                'label' => 'Executivo',
                'href' => $url('') . '#dash-executivo',
                'icon' => 'bi-bar-chart-steps',
                'active' => false,
            ];
        }
        $groups[] = ['id' => 'dashboard', 'label' => 'Dashboard', 'items' => $dashboardItems];

        $cadastros = [];
        if ($canClientes)
        {
            $cadastros[] = [
                'label' => 'Clientes',
                'href' => $url('customers'),
                'icon' => 'bi-people',
                'active' => $navPrefix('/customers') !== '',
            ];
        }
        if ($canProdutos)
        {
            $cadastros[] = [
                'label' => 'Produtos',
                'href' => $url('products'),
                'icon' => 'bi-box-seam',
                'active' => $navPrefix('/products') !== '',
            ];
            $cadastros[] = [
                'label' => 'Serviços',
                'href' => $url('services'),
                'icon' => 'bi-tools',
                'active' => $navPrefix('/services') !== '',
            ];
            $cadastros[] = [
                'label' => 'Categorias',
                'href' => $url('categories'),
                'icon' => 'bi-tags',
                'active' => $navPrefix('/categories') !== '',
            ];
        }
        if ($isPlatformAdmin)
        {
            $cadastros[] = [
                'label' => 'Empresas',
                'href' => $url('admin/companies'),
                'icon' => 'bi-buildings',
                'active' => $navPrefix('/admin/companies') !== '',
            ];
        }
        if ($cadastros !== [])
        {
            $groups[] = ['id' => 'cadastros', 'label' => 'Cadastros', 'items' => $cadastros];
        }

        $comercial = [];
        if ($canVendas)
        {
            $comercial[] = [
                'label' => 'Vendas',
                'href' => $url('orders'),
                'icon' => 'bi-receipt',
                'active' => $navPrefix('/orders') !== '',
            ];
            if (Permission::can('vendas', 'criar'))
            {
                $comercial[] = [
                    'label' => 'Nova venda',
                    'href' => $url('orders/create'),
                    'icon' => 'bi-plus-circle',
                    'active' => $navPrefix('/orders/create') !== '',
                ];
            }
            $comercial[] = [
                'label' => 'Parcelamentos',
                'href' => $url('finance/installments/open'),
                'icon' => 'bi-calendar2-check',
                'active' => $navPrefix('/finance/installments') !== '',
            ];
        }
        if ($comercial !== [])
        {
            $groups[] = ['id' => 'comercial', 'label' => 'Comercial', 'items' => $comercial];
        }

        $financeiro = [];
        if ($canFinanceiro)
        {
            $financeiro[] = [
                'label' => 'Painel financeiro',
                'href' => $url('finance'),
                'icon' => 'bi-cash-stack',
                'active' => $currentPath === '/finance',
            ];
            $financeiro[] = [
                'label' => 'Fluxo de caixa',
                'href' => $url('finance/cash-flow'),
                'icon' => 'bi-arrow-left-right',
                'active' => $navPrefix('/finance/cash-flow') !== '',
            ];
            $financeiro[] = [
                'label' => 'Contas a receber',
                'href' => $url('finance/accounts-receivable'),
                'icon' => 'bi-wallet2',
                'active' => $navPrefix('/finance/accounts-receivable') !== '',
            ];
            $financeiro[] = [
                'label' => 'Parcelas vencidas',
                'href' => $url('finance/installments/overdue'),
                'icon' => 'bi-exclamation-triangle',
                'active' => $currentPath === '/finance/installments/overdue',
            ];
        }
        if ($financeiro !== [])
        {
            $groups[] = ['id' => 'financeiro', 'label' => 'Financeiro', 'items' => $financeiro];
        }

        $estoque = [];
        if ($canEstoque)
        {
            $estoque[] = [
                'label' => 'Movimentações',
                'href' => $url('stock-movements'),
                'icon' => 'bi-archive',
                'active' => $navPrefix('/stock-movements') !== '',
            ];
            if ($canRelatorios)
            {
                $estoque[] = [
                    'label' => 'Alertas de estoque',
                    'href' => $url('reports/low-stock'),
                    'icon' => 'bi-exclamation-circle',
                    'active' => $navPrefix('/reports/low-stock') !== '',
                ];
            }
        }
        if ($estoque !== [])
        {
            $groups[] = ['id' => 'estoque', 'label' => 'Estoque', 'items' => $estoque];
        }

        $relatorios = [];
        if ($canRelatorios)
        {
            $relatorios[] = [
                'label' => 'Central de relatórios',
                'href' => $url('reports'),
                'icon' => 'bi-bar-chart-line',
                'active' => $currentPath === '/reports',
            ];
            if ($canVendas)
            {
                $relatorios[] = [
                    'label' => 'Vendas por período',
                    'href' => $url('reports/sales-period'),
                    'icon' => 'bi-calendar-range',
                    'active' => $navPrefix('/reports/sales-period') !== '',
                ];
                $relatorios[] = [
                    'label' => 'Top produtos',
                    'href' => $url('reports/top-products'),
                    'icon' => 'bi-trophy',
                    'active' => $navPrefix('/reports/top-products') !== '',
                ];
            }
            if ($canFinanceiro)
            {
                $relatorios[] = [
                    'label' => 'Fluxo de caixa',
                    'href' => $url('reports/cash-flow'),
                    'icon' => 'bi-graph-down-arrow',
                    'active' => $navPrefix('/reports/cash-flow') !== '',
                ];
            }
        }
        if ($relatorios !== [])
        {
            $groups[] = ['id' => 'relatorios', 'label' => 'Relatórios', 'items' => $relatorios];
        }

        $groups[] = [
            'id' => 'comunicacao',
            'label' => 'Comunicação',
            'items' => [
                [
                    'label' => 'Notificações',
                    'href' => $url('notifications'),
                    'icon' => 'bi-bell',
                    'active' => $navPrefix('/notifications') !== '',
                    'badge' => ($notificationUnreadCount ?? 0) > 0
                        ? (($notificationUnreadCount > 99) ? '99+' : $notificationUnreadCount)
                        : null,
                ],
            ],
        ];

        $admin = [];
        if ($canUsuarios)
        {
            $admin[] = [
                'label' => 'Auditoria',
                'href' => $url('audit-logs'),
                'icon' => 'bi-journal-text',
                'active' => $navPrefix('/audit-logs') !== '',
            ];
            $admin[] = [
                'label' => 'Logs de acesso',
                'href' => $url('access-logs'),
                'icon' => 'bi-door-open',
                'active' => $navPrefix('/access-logs') !== '',
            ];
            $admin[] = [
                'label' => 'Backup',
                'href' => $url('backups'),
                'icon' => 'bi-hdd-stack',
                'active' => $navPrefix('/backups') !== '',
            ];
            $admin[] = [
                'label' => 'Assinatura',
                'href' => $url('subscription'),
                'icon' => 'bi-credit-card',
                'active' => $navActive('/subscription') !== '',
            ];
        }
        if ($isPlatformAdmin)
        {
            $admin[] = [
                'label' => 'Usuários',
                'href' => $url('admin/users'),
                'icon' => 'bi-person-gear',
                'active' => $navPrefix('/admin/users') !== '',
            ];
            $admin[] = [
                'label' => 'SaaS',
                'href' => $url('admin/saas'),
                'icon' => 'bi-cloud',
                'active' => $navPrefix('/admin/saas') !== '',
            ];
        }
        if ($canManageLinks)
        {
            $admin[] = [
                'label' => 'Vínculos usuário-empresa',
                'href' => $url('user-companies'),
                'icon' => 'bi-link-45deg',
                'active' => $navPrefix('/user-companies') !== '',
            ];
        }
        if ($admin !== [])
        {
            $groups[] = ['id' => 'administracao', 'label' => 'Administração', 'items' => $admin];
        }

        if ($canProdutos || $canVendas)
        {
            $apiItems = [];
            if ($canProdutos)
            {
                $apiItems[] = [
                    'label' => 'API Produtos',
                    'href' => $url('api/products'),
                    'icon' => 'bi-braces',
                    'active' => false,
                    'external' => true,
                ];
            }
            if ($canVendas)
            {
                $apiItems[] = [
                    'label' => 'API Pedidos',
                    'href' => $url('api/orders'),
                    'icon' => 'bi-braces',
                    'active' => false,
                    'external' => true,
                ];
            }
            $groups[] = ['id' => 'api', 'label' => 'API', 'items' => $apiItems];
        }

        return $groups;
    }
}
