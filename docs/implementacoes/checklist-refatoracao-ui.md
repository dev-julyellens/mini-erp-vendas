# Checklist priorizada — Refatoração UI/UX

Checklist derivada de [`.cursor/prompts/gerenciamento-completo.md`](../../.cursor/prompts/gerenciamento-completo.md) e do estado atual do código (maio/2026).

**Legenda por tarefa**

| Sigla | Significado |
|-------|-------------|
| **PH** | Adotar `components/page-header.php` |
| **AB-R** | Adotar `action-buttons.php` modo `table-row` |
| **AB-F** | Adotar `action-buttons.php` modo `form-footer` |
| **AB-Flt** | Adotar `action-buttons.php` modo `filter` |
| **FP** | Envolver filtros em `card-soft filter-panel` + `filter-form` |
| **MSK** | Máscaras (`data-mask-phone`, `data-mask-document`, `data-mask-cep`, `data-mask-money`) |
| **LD** | `data-loading-text` em submit |
| **KPI** | Usar `kpi-card.php` em resumos (relatórios/dashboard) |

**Status da tela**

| Ícone | Significado |
|-------|-------------|
| ✅ | Referência / já alinhada ao padrão novo |
| 🟡 | Parcialmente migrada |
| ⬜ | Pendente de migração |

---

## Fase 0 — Infraestrutura (desbloqueia o rollout)

Fazer **antes** ou **em paralelo** às ondas 1–3 para evitar copiar HTML em dezenas de arquivos.

| # | Tarefa | Arquivo sugerido | Prioridade |
|---|--------|------------------|------------|
| 0.1 | Partial de painel de filtros reutilizável | `app/Views/components/filter-panel.php` | ✅ Feito |
| 0.2 | Documentar template mínimo de listagem (PH + tabela + AB-R) | `docs/implementacoes/botoes-padrao.md` | ✅ Feito |
| 0.3 | Preferências no servidor (`user_preferences`) | migration + `ProfileService` | ✅ Feito |
| 0.4 | Upload de avatar | migration `users.avatar_path` + controller | ✅ Feito |
| 0.5 | Indicador de força de senha | `profile/password.php` + JS | ✅ Feito |
| 0.6 | Skeleton em carregamentos AJAX | views + `app.js` | ✅ Feito |
| 0.7 | Sidebar pin (fixar/desfixar) | `sidebar.php` + `design-system.css` + `app.js` | ✅ Feito |
| 0.8 | Gráficos embutidos em PDF | `ReportExportService` + `ReportChartImageService` (GD) | ✅ Feito |

---

## Fase 1 — Alto impacto operacional (uso diário)

### Comercial — Vendas

| Arquivo | Status | PH | AB-R | AB-F | AB-Flt | FP | MSK | LD | Notas |
|---------|--------|----|------|------|--------|----|-----|----|-------|
| `orders/index.php` | ✅ | ✅ | ✅ | — | ✅ | ✅ | — | — | Referência comercial (filter-panel + PH) |
| `orders/create.php` | ✅ | — | — | ✅ | — | — | — | — | PH + breadcrumbs; AJAX + autosave local |
| `orders/show.php` | ✅ | — | — | — | — | — | — | — | PH + breadcrumbs; ações no header |

### Cadastros — já referência

| Arquivo | Status | PH | AB-R | AB-F | AB-Flt | FP | MSK | LD | Notas |
|---------|--------|----|------|------|--------|----|-----|----|-------|
| `customers/index.php` | ✅ | ✅ | ✅ | — | — | — | — | — | **Modelo de listagem** |
| `customers/form.php` | ✅ | ✅ | — | ✅ | — | — | ✅ phone | ✅ | Breadcrumbs + `page-header` |
| `products/index.php` | ✅ | ✅ | ✅ | — | ✅ | ✅ | — | — | |
| `products/form.php` | ✅ | ✅ | — | ✅ | — | — | ✅ moeda | ✅ | `data-mask-money` + autosave local |
| `services/index.php` | ✅ | ✅ | ✅ | — | ✅ | ✅ | — | — | |
| `services/form.php` | ✅ | ✅ | — | ✅ | — | — | ✅ moeda | ✅ | `data-mask-money` + `service_form.js` |
| `categories/index.php` | ✅ | ✅ | ✅ | — | — | — | — | — | |
| `categories/form.php` | ✅ | ✅ | — | ✅ | — | — | — | ✅ | |

### Financeiro

| Arquivo | Status | PH | AB-R | AB-F | AB-Flt | FP | MSK | LD | Notas |
|---------|--------|----|------|------|--------|----|-----|----|-------|
| `finance/index.php` | ✅ | ✅ | — | — | — | — | — | — | KPIs com `kpi-card` |
| `finance/cash-flow.php` | ✅ | ✅ | — | — | ✅ | ✅ | — | — | |
| `finance/accounts-receivable/index.php` | ✅ | ✅ | ✅ | — | ✅ | ✅ | — | — | `extraActions` Receber |
| `finance/accounts-receivable/show.php` | ✅ | ✅ | — | — | — | — | — | — | |
| `finance/accounts-receivable/receive.php` | ✅ | ✅ | — | ✅ | — | — | — | ✅ | AB-F + loading |
| `finance/installments/list.php` | ✅ | ✅ | ✅ | — | ✅ | ✅ | — | — | |
| `finance/installments/pay.php` | ✅ | ✅ | — | ✅ | — | — | — | ✅ | AB-F + loading |
| `finance/pix/charge.php` | ✅ | ✅ | — | — | — | — | — | — | |
| `finance/pix/receipt.php` | ✅ | ✅ | — | — | — | — | — | — | |

### Estoque

| Arquivo | Status | PH | AB-R | AB-F | AB-Flt | FP | MSK | LD | Notas |
|---------|--------|----|------|------|--------|----|-----|----|-------|
| `stock/index.php` | ✅ | ✅ | — | — | ✅ | ✅ | — | — | Listagem sem ações de linha |
| `stock/create.php` | ✅ | ✅ | — | ✅ | — | — | — | ✅ | |

### Dashboard

| Arquivo | Status | PH | KPI | Notas |
|---------|--------|----|-----|-------|
| `dashboard/index.php` | ✅ | ✅ | ✅ | Abas + KPIs — manter como referência |

---

## Fase 2 — Administração e multiempresa

| Arquivo | Status | PH | AB-R | AB-F | AB-Flt | FP | Notas |
|---------|--------|----|------|------|--------|----|-------|
| `users/index.php` | ✅ | ✅ | ✅ | — | ✅ | ✅ | `extraActions` (Senha + toggle) |
| `users/form.php` | ✅ | ✅ | — | ✅ | — | — | |
| `users/reset-password.php` | ✅ | ✅ | — | ✅ | — | — | |
| `companies/index.php` | ✅ | ✅ | ✅ | — | ✅ | ✅ | `extraActions` (ativar/desativar) |
| `companies/form.php` | ✅ | ✅ | — | ✅ | — | — | `tax_id` com `data-mask-document` |
| `user-companies/index.php` | ✅ | ✅ | — | — | ✅ | ✅ | Ações inline (papel/select) |
| `admin/saas/index.php` | ✅ | ✅ | — | — | — | — | KPIs com `kpi-card` |
| `admin/saas/subscriptions.php` | ✅ | ✅ | — | — | ✅ | ✅ | |
| `audit/index.php` | ✅ | ✅ | — | — | ✅ | ✅ | Modal “Ver” (btn-ghost) |
| `access-logs/index.php` | ✅ | ✅ | — | — | ✅ | ✅ | |
| `backups/index.php` | ✅ | ✅ | — | — | — | — | PH + ações no header |
| `subscription/show.php` | ✅ | ✅ | — | — | — | — | `data-loading-text` nos submits do plano |

### Formulários CRUD — migração Sprint 6 ✅

Os 7 formulários abaixo foram migrados para `page-header` (com breadcrumbs) + `saveLoadingText` no `form-footer`. Pendência remanescente: máscara de **moeda** nos campos de preço de produto/serviço (tratados por JS dedicado).

| Arquivo | Status | PH | AB-F | LD | MSK |
|---------|--------|----|------|----|-----|
| `customers/form.php` | ✅ | ✅ | ✅ | ✅ | ✅ phone |
| `products/form.php` | ✅ | ✅ | ✅ | ✅ | ✅ moeda |
| `services/form.php` | ✅ | ✅ | ✅ | ✅ | ✅ moeda |
| `categories/form.php` | ✅ | ✅ | ✅ | ✅ | — |
| `users/form.php` | ✅ | ✅ | ✅ | ✅ | — |
| `users/reset-password.php` | ✅ | ✅ | ✅ | ✅ | — |
| `companies/form.php` | ✅ | ✅ | ✅ | ✅ | ✅ `tax_id` |

---

## Fase 3 — Relatórios (tela + export)

Export PDF/Excel já tem KPIs (`ReportExportService`). Foco: **experiência na tela**.

| Arquivo | Status | PH | AB-Flt | KPI | Gráfico | Notas |
|---------|--------|----|--------|-----|---------|-------|
| `reports/index.php` | ✅ | ✅ | — | — | — | Central de relatórios |
| `reports/sales-period.php` | ✅ | ✅ | ✅ | ✅ | ✅ | Linha — receita por dia |
| `reports/sales-product.php` | ✅ | ✅ | ✅ | ✅ | ✅ | Barras horiz. top receita |
| `reports/sales-customer.php` | ✅ | ✅ | ✅ | ✅ | ✅ | Barras horiz. top clientes |
| `reports/cash-flow.php` | ✅ | ✅ | ✅ | ✅ | ✅ | Barras empilhadas entradas/saídas |
| `reports/low-stock.php` | ✅ | ✅ | ✅ | ✅ | ✅ | Barras horiz. déficit |
| `reports/top-products.php` | ✅ | — | — | — | — | Reutiliza `sales-product.php` |
| `reports/partials/export-buttons.php` | ✅ | — | — | — | — | |
| `reports/_report-header.php` | ✅ | ✅ | — | — | — | Usa `page-header` + export |

---

## Fase 4 — Perfil, comunicação e layout

| Arquivo | Status | Tarefas pendentes |
|---------|--------|-------------------|
| `profile/show.php` | ✅ | Avatar upload + prefs servidor sincronizadas |
| `profile/password.php` | ✅ | PH + AB-F + LD + indicador de força de senha |
| `notifications/index.php` | ✅ | PH; filter-panel; ação no header |
| `layouts/main.php` | ✅ | — |
| `layouts/auth.php` | ✅ | Tema claro/escuro + contraste WCAG (`--auth-*`, `auth-link`) |
| `components/sidebar.php` | ✅ | Recolher/expandir (`data-sidebar-collapse`); fixar só no perfil |
| `components/page-header.php` | ✅ | — |
| `components/action-buttons.php` | ✅ | — |
| `components/kpi-card.php` | ✅ | — |

---

## Fase 5 — Telas secundárias / baixa frequência ✅

Partial `auth-form-header.php` para telas no layout `auth` (evita duplicar título da marca). `design-system.css` incluído em `layouts/auth.php`.

| Arquivo | Status | PH | AB-F | LD | Notas |
|---------|--------|----|------|-----|-------|
| `auth/login.php` | ✅ | — | — | ✅ | título no layout auth-card |
| `auth/select-company.php` | ✅ | auth-hdr | — | ✅ | `form-actions` |
| `auth/forgot-password.php` | ✅ | auth-hdr | — | ✅ | |
| `auth/reset-password.php` | ✅ | auth-hdr | — | ✅ | hint via `passwordHint` |
| `onboarding/company.php` | ✅ | auth-hdr | — | ✅ | |
| `onboarding/plan.php` | ✅ | auth-hdr | — | ✅ | |
| `lgpd/consent.php` | ✅ | auth-hdr | — | ✅ | |
| `profile/password.php` | ✅ | page-header | ✅ | ✅ | ver inventário principal |
| `finance/pix/*` | ✅ | page-header | — | — | ver inventário principal |
| `subscription/show.php` | ✅ | page-header | — | ✅ | |

**Gráficos na tela:** Chart.js nos relatórios com dados do filtro completo (não só a página da tabela).

---

## Menu — alinhamento com o prompt

**Simplificado (mai/2026):** Dashboard e Relatórios com um único item no sidebar cada (`Dashboard` → `/`; `Central de relatórios` → `/reports/*`). Abas internas do dashboard e cards da central cobrem os atalhos.

Itens do documento ainda **não refletidos** em `NavigationMenu.php`:

| Item sugerido no prompt | Ação sugerida |
|------------------------|---------------|
| Pedidos vs Orçamentos | Avaliar se há módulo de orçamento; senão renomear/agrupar em submenu |
| Recebimentos | Link direto para `finance/accounts-receivable` ou fluxo de recebimento |
| Histórico financeiro | Item para relatório ou listagem consolidada |
| Inventário | Item para tela de inventário (se existir) ou documentar como futuro |
| Permissões (menu Admin) | Link para âncora em `/profile#permissoes` ou tela dedicada |

---

## Ordem de execução recomendada (sprints)

### Sprint 1 — Componente + comercial (1–2 dias) ✅

1. ~~`filter-panel.php` (Fase 0.1)~~
2. ~~`orders/index.php` — PH + AB-Flt + AB-R~~
3. ~~`orders/create.php` — AB-F~~
4. ~~`orders/show.php` — PH~~

### Sprint 2 — Cadastros restantes (1 dia) ✅

1. ~~`services/index.php`, `categories/index.php` — PH + AB-R~~
2. ~~`products/index.php` — PH + filter-panel~~
3. ~~`companies/index.php`, `users/index.php` — PH + AB-R + filter-panel~~

### Sprint 3 — Financeiro (1–2 dias) ✅

1. ~~`finance/index.php` — PH + KPIs~~
2. ~~`finance/accounts-receivable/*`, `installments/*`, `cash-flow.php`~~
3. ~~Formulários: `receive.php`, `pay.php` — AB-F + LD~~

### Sprint 4 — Estoque, notificações, admin (1 dia) ✅

1. ~~`stock/*`, `notifications/index.php`~~
2. ~~`audit`, `access-logs`, `backups`, `user-companies`~~
3. ~~`admin/saas/*`~~

### Sprint 5 — Relatórios gerenciais na tela (2–3 dias) ✅

1. ~~Migrar cards de resumo para `kpi-card.php`~~
2. ~~Unificar cabeçalho via `_report-header.php` → `page-header`~~
3. ~~`filter-panel` em todos os relatórios com filtros~~
4. ~~Gráficos embutidos na tela (Chart.js) — `ReportChartService`, `report-charts.php`, `reports-charts.js`~~

### Sprint 6 — Formulários CRUD + infra avançada ✅

1. ~~**Formulários:** migrar os 7 arquivos para `page-header` + `saveLoadingText`~~ ✅
2. ~~Avatar, prefs servidor, skeleton, pin sidebar, PDF com gráficos (Fase 0.3–0.8)~~ ✅
3. ~~Autosave em `orders/create`, `products/form`~~ ✅
4. ~~Máscaras globais (documento, CEP, moeda) — `input-masks.js`~~ ✅
5. ~~Revisão acessibilidade (audit com axe ou checklist WCAG) — `a11y.js`, skip link, ARIA tabs/dashboard, doc `acessibilidade.md`~~ ✅
6. ~~`layouts/auth.php` — contraste tema claro/escuro (`--auth-*`, `.auth-card`, links `auth-link`)~~ ✅

---

## Template rápido — listagem

Copiar de `customers/index.php`:

```php
<?php
$title = 'Título';
$subtitle = 'Descrição curta';
$actionsHtml = ''; // botão Novo se aplicável
require dirname(__DIR__) . '/components/page-header.php';
?>

<div class="card-soft filter-panel p-3 p-md-4 mb-3">
    <form class="row g-2 align-items-end filter-form" method="get" action="...">
        <!-- campos -->
        <?php
        $mode = 'filter';
        $clearHref = $url('rota');
        require dirname(__DIR__) . '/components/action-buttons.php';
        ?>
    </form>
</div>

<div class="card-soft p-3 p-md-4">
    <table class="table ...">
        <!-- linha -->
        <?php
        $mode = 'table-row';
        $editHref = ...;
        $deleteAction = ...;
        // $canEdit, $canDelete
        require dirname(__DIR__) . '/components/action-buttons.php';
        ?>
    </table>
</div>
```

---

## Template rápido — formulário

```php
<?php
// ... campos ...
$mode = 'form-footer';
$cancelHref = $url('rota-lista');
$saveLoadingText = 'Salvando...';
require dirname(__DIR__) . '/components/action-buttons.php';
```

---

## Progresso estimado

| Área | Concluído (aprox.) | Observação |
|------|-------------------|------------|
| Design system + layout | ~92% | `design-system.css` no layout principal e auth |
| Menu / sidebar | ~75% | Itens do prompt ainda não refletidos (ver seção Menu) |
| Dashboard | ~90% | PH + KPIs + abas |
| Perfil | ~95% | Avatar, prefs servidor, força de senha |
| Export relatórios | ~95% | PDF com KPIs + gráfico (GD) |
| **Listagens (index)** | **~98%** | PH + AB-R + filter-panel onde há filtros |
| **Formulários CRUD** | **~98%** | PH + AB-F + LD; máscaras phone/document/money |
| Componentização (partials) | ~90% | `filter-panel`, `action-buttons`, `page-header` maduros |
| Telas secundárias (auth/onboarding/LGPD) | ~95% | `auth-form-header` + loading nos submits |
| Relatórios na tela (BI) | ~95% | KPIs + Chart.js nos 5 relatórios principais |
| Acessibilidade / performance visual | ~30% | Sem auditoria automatizada ainda |

**Última atualização:** 26/05/2026 — Sprint 6 item 4: máscaras globais (`input-masks.js`).
