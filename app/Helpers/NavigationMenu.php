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
 *   children?: list<array<string, mixed>>
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

        $groups[] = [
            'id' => 'dashboard',
            'label' => 'Dashboard',
            'items' => [
                [
                    'label' => 'Dashboard',
                    'href' => $url(''),
                    'icon' => 'bi-speedometer2',
                    'active' => $navActive('/') !== '',
                ],
            ],
        ];

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
            $comercial[] = [
                'label' => 'Orçamentos',
                'href' => $url('quotes'),
                'icon' => 'bi-file-earmark-text',
                'active' => $navPrefix('/quotes') !== '',
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
                'label' => 'Recebimentos',
                'href' => $url('finance/accounts-receivable'),
                'icon' => 'bi-wallet2',
                'active' => $navPrefix('/finance/accounts-receivable') !== '',
            ];
            $financeiro[] = [
                'label' => 'Parcelas',
                'href' => $url('finance/installments/open'),
                'icon' => 'bi-calendar2-check',
                'active' => $navPrefix('/finance/installments') !== '',
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
            $estoque[] = [
                'label' => 'Inventário físico',
                'href' => $url('inventory'),
                'icon' => 'bi-clipboard-check',
                'active' => $navPrefix('/inventory') !== '',
            ];
        }
        if ($estoque !== [])
        {
            $groups[] = ['id' => 'estoque', 'label' => 'Estoque', 'items' => $estoque];
        }

        if ($canRelatorios)
        {
            $groups[] = [
                'id' => 'relatorios',
                'label' => 'Relatórios',
                'items' => [
                    [
                        'label' => 'Central de relatórios',
                        'href' => $url('reports'),
                        'icon' => 'bi-bar-chart-line',
                        'active' => $navPrefix('/reports') !== '',
                    ],
                ],
            ];
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
                'label' => 'Permissões',
                'href' => $url('profile') . '#permissoes',
                'icon' => 'bi-shield-lock',
                'active' => $navPrefix('/profile') !== '',
            ];
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
