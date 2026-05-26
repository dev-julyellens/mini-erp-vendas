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
- Componentes PHP reutilizáveis em `app/Views/components/`
- Botão destrutivo suave (`btn-danger-soft`)
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
- Máscara de telefone (`data-mask-phone`) em clientes
- Cabeçalho padronizado em clientes (modelo para demais telas)
- Loading em botões de submit

## Componentes padronizados

| Componente | Arquivo |
|------------|---------|
| Sidebar | `app/Views/components/sidebar.php` |
| Cabeçalho de página | `app/Views/components/page-header.php` |
| Card KPI | `app/Views/components/kpi-card.php` |
| Menu (helper) | `app/Helpers/NavigationMenu.php` |

## Telas refatoradas

- Layout global (`layouts/main.php`)
- Dashboard (`dashboard/index.php`)
- Perfil (`profile/show.php`)
- Clientes — lista e formulário (`customers/index.php`, `customers/form.php`)
- Exportação de relatórios (serviço PDF/Excel)

## Menus reorganizados

- Dashboard (visão + atalhos por área)
- Cadastros (clientes, produtos, serviços, categorias, empresas)
- Comercial (vendas, nova venda, parcelamentos)
- Financeiro (painel, fluxo, contas a receber, vencidas)
- Estoque (movimentações, alertas)
- Relatórios (central + principais)
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

## Pendências sugeridas (próximas iterações)

- Aplicar `page-header` e `btn-danger-soft` em todas as listagens/CRUDs restantes
- Upload de avatar (requer coluna/migration em `users`)
- Skeleton loading em carregamentos AJAX
- Autosave em formulários longos
- Gráficos embutidos nos PDFs (requer biblioteca adicional)
- Sincronizar preferências no servidor (tabela `user_preferences`)
