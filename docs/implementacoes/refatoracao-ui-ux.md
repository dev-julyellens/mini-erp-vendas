# Refatoração UI/UX — Gerenciamento completo

Documentação da refatoração visual e de experiência do Mini ERP de Vendas.

## Bug crítico corrigido

**Erro:** `SQLSTATE[42703]: coluna "role" não existe` em consultas a `user_companies`.

**Causa:** Banco sem a migration `019_user_companies_roles.sql` (colunas `role`, `active`, timestamps).

**Correção:**
- Migration idempotente já existente em `database/migrations/019_user_companies_roles.sql`
- `database/database.sql` atualizado para instalações novas
- Executar em ambientes afetados: `php database/run_migration.php`

## Melhorias implementadas

### Padronização visual
- Design system em `public/assets/css/design-system.css` (tokens, botões, badges, KPIs, formulários, dashboard tabs, skeleton, acessibilidade)
- Componentes PHP reutilizáveis em `app/Views/components/` (incl. `filter-panel.php` — Sprint 1)
- **Vendas (pedidos):** `orders/index`, `orders/create`, `orders/show` migrados (page-header, filter-panel, action-buttons)
- **Cadastros/admin (Sprint 2):** `products`, `services`, `categories`, `companies`, `users` (index) — page-header, filter-panel e action-buttons
- **Financeiro (Sprint 3):** painel, fluxo de caixa, contas a receber, parcelamentos — page-header, filter-panel, kpi-card, action-buttons
- **Operacional/admin (Sprint 4):** estoque, notificações, auditoria, access-logs, backups, vínculos, SaaS — page-header e filter-panel
- **Relatórios (Sprint 5):** central e relatórios com filtros — `_report-header` (page-header + export), `filter-panel`, `kpi-card` nos resumos
- **Gráficos nos relatórios:** `ReportChartService` + partial `report-charts.php` + `reports-charts.js` (vendas por período/cliente/produto, fluxo de caixa, estoque mínimo)
- **Telas secundárias (Fase 5):** auth, onboarding, LGPD, alterar senha, PIX e assinatura — `auth-form-header.php` ou `page-header`, loading nos submits, `design-system.css` no layout auth
- **Botões de ação:** variantes `primary`, `secondary`, `outline`, `destructive`, `warning`, `ghost` + tamanhos `sm`/`md`/`lg` — ver [botoes-padrao.md](./botoes-padrao.md)
- Partial `action-buttons.php` e helper `ActionButton.php`
- Dark/light: ajustes de contraste no topbar e componentes

### Menu e sidebar
- Navegação agrupada via `app/Helpers/NavigationMenu.php`
- Sidebar em `app/Views/components/sidebar.php` com submenus colapsáveis
- Recolhimento persistente (`mini-erp-sidebar-collapsed` no localStorage)
- Estado dos grupos persistido (`mini-erp-nav-groups`)
- Layout principal refatorado em `app/Views/layouts/main.php`

### Dashboard gerencial
- Abas: Visão geral, Comercial, Financeiro, Operacional, Executivo
- KPIs por painel com componente `kpi-card.php`
- Gráficos reorganizados por contexto
- Preferência de aba favorita persistida

### Perfil do usuário
- Avatar por iniciais
- Preferências de tema, sidebar e dashboard (localStorage)
- Lista de empresas vinculadas com papel
- Visualização de permissões efetivas (ACL)
- Formulário com validação visual aprimorada

### Relatórios
- PDF com cabeçalho corporativo, KPIs e rodapé
- Excel com linha de KPIs e metadados
- Barra de exportação estilizada

### Formulários e listagens
- Máscaras em `public/assets/js/input-masks.js`: telefone, documento (CPF/CNPJ), CEP, moeda — ver `docs/implementacoes/mascaras-formulario.md`
- Cabeçalho padronizado em clientes (modelo para demais telas)
- Loading em botões de submit

## Componentes padronizados

| Componente | Arquivo |
|------------|---------|
| Sidebar | `app/Views/components/sidebar.php` |
| Cabeçalho de página | `app/Views/components/page-header.php` |
| Card KPI | `app/Views/components/kpi-card.php` |
| Botões de ação | `app/Views/components/action-buttons.php` |
| Cabeçalho auth/onboarding | `app/Views/components/auth-form-header.php` |
| Menu (helper) | `app/Helpers/NavigationMenu.php` |
| Classes de botão | `app/Helpers/ActionButton.php` |

## Telas refatoradas

- Layout global (`layouts/main.php`)
- Dashboard (`dashboard/index.php`)
- Perfil (`profile/show.php`)
- Clientes, produtos, serviços, categorias — listas e formulários
- Usuários, empresas, vínculos — listagens administrativas
- Vendas, estoque, notificações, financeiro (principais)
- Dashboard (atalhos), relatórios (filtros + export)
- Exportação de relatórios (serviço PDF/Excel)

## Menus reorganizados

- Dashboard (item único no menu; abas Comercial / Financeiro / Operacional / Executivo na tela)
- Cadastros (clientes, produtos, serviços, categorias, empresas)
- Comercial (vendas, nova venda, parcelamentos)
- Financeiro (painel, fluxo, contas a receber, vencidas)
- Estoque (movimentações)
- Relatórios (central de relatórios; demais relatórios só pela central)
- Comunicação (notificações)
- Administração (auditoria, logs, backup, assinatura, usuários, SaaS, vínculos)
- API (links externos)

## Padrões visuais e arquitetura de UI

- **CSS:** `app.css` (base/layout) + `design-system.css` (componentes)
- **JS:** `app.js` — tema, sidebar, grupos de menu, abas do dashboard, máscaras, DataTables
- **Preferências:** localStorage (`mini-erp-theme`, `mini-erp-sidebar-collapsed`, `mini-erp-dashboard-tab`, `mini-erp-nav-groups`)
- **Views:** componentes em `app/Views/components/`, partials existentes preservados

## Como testar manualmente

1. **Migration role:** `php database/run_migration.php` — confirmar que notificações e troca de empresa funcionam sem erro SQL.
2. **Menu:** expandir/recolher grupos; recolher sidebar (desktop); testar mobile (hamburger).
3. **Tema:** alternar claro/escuro no topbar e no perfil; recarregar página.
4. **Dashboard:** navegar pelas abas; verificar gráficos na aba Comercial/Executivo.
5. **Perfil:** editar nome/e-mail; ver empresas e permissões; alterar preferências.
6. **Clientes:** criar/editar com máscara de telefone.
7. **Relatórios:** exportar PDF/Excel e verificar KPIs no cabeçalho.

## Checklist priorizada (arquivo a arquivo)

Ver **[checklist-refatoracao-ui.md](./checklist-refatoracao-ui.md)** — sprints, status por view, templates de migração e itens de menu pendentes.

## Pendências sugeridas (próximas iterações)

- Gráficos nos PDFs de relatório (biblioteca adicional)
- Aplicar `page-header` em `customers/index.php` (modelo de listagem, ainda sem PH)
- Adotar partial `filter` em todos os painéis de filtro
- Upload de avatar (requer coluna/migration em `users`)
- Skeleton loading em carregamentos AJAX
- Autosave em formulários longos
- Gráficos embutidos nos PDFs (requer biblioteca adicional)
- Sincronizar preferências no servidor (tabela `user_preferences`)
