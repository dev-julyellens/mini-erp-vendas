# Acessibilidade (WCAG 2.1 — nível AA alvo)

Revisão do Sprint 6, item 5: melhorias no layout, componentes e scripts, com checklist manual alinhado às diretrizes WCAG 2.1.

**Última auditoria documentada:** 26/05/2026 (Sprint F).

## O que foi implementado

### Navegação e estrutura

| Recurso | Onde |
|--------|------|
| Skip link (“Ir para o conteúdo principal”) | `layouts/main.php`, `layouts/auth.php` |
| `<main id="mainContent" tabindex="-1">` | `layouts/main.php` — destino do skip link |
| `<main id="authMain" tabindex="-1">` | `layouts/auth.php` |
| `role="banner"` na topbar | `layouts/main.php` |
| `lang="pt-BR"` | layouts |
| Título dinâmico da aba (`h1` → `<title>`) | `public/assets/js/a11y.js` |

### Teclado e foco

- `:focus-visible` global em `design-system.css`
- Abas do dashboard: `tabindex`, `aria-controls`, `aria-labelledby`, setas ←/→, Home/End (`app.js` + `a11y.js`)
- Abas do formulário de produto: `role="tab"` / `tabpanel` com IDs (`products/form.php`)

### Leitores de tela

- Região `aria-live="polite"` (`#srAnnounce`) para loading global e mensagens auxiliares
- `#globalLoading` com `role="status"` e `aria-busy`
- Sininho de notificações: `aria-label` com contagem; badge decorativo com `aria-hidden`
- Botão Sair: `aria-label="Sair da conta"`
- Modal de confirmação: `aria-describedby` na mensagem
- Tabelas `js-datatable`: `aria-label` automático a partir do `h1` da página (ou explícito, ex.: clientes)
- Campo de busca do DataTables: `aria-label` após init e em cada `draw` (`a11y.js` + `app.js`)
- Gráficos Chart.js: `role="img"`, `figure`/`figcaption` e tabela `.visually-hidden` com dados (`ChartA11yHelper`, `chart-sr-summary.php`)

### Formulários

- Labels visíveis nos filtros (padrão `filter-panel`)
- Máscaras não removem `name`/`id` dos campos
- Erros de validação: classes `is-invalid` + texto de erro (Bootstrap)

## Checklist WCAG (manual)

Roteiro validado na amostra das 5 páginas prioritárias (26/05/2026). Marque ✅ quando ok na amostra testada.

### Perceptível

- [x] **1.1.1** Imagens/ícones decorativos com `aria-hidden="true"` quando não transmitem informação
- [x] **1.3.1** Cabeçalhos em ordem lógica (`h1` por página via `page-header`)
- [x] **1.4.3** Contraste texto ≥ 4.5:1 (tema claro e escuro) — tokens `--auth-*` nas telas de acesso
- [x] **1.4.11** Contraste de componentes UI (bordas, botões secundários) — design system + tema escuro

### Operável

- [x] **2.1.1** Todas as ações principais acessíveis só com teclado
- [x] **2.4.1** Skip link visível ao focar
- [x] **2.4.2** Título da página reflete o conteúdo (`a11y.js`)
- [x] **2.4.3** Ordem de foco coerente (sidebar → topbar → conteúdo)
- [x] **2.4.4** Links com texto ou `aria-label` compreensível
- [x] **2.4.6** Rótulos de formulário associados (`for` / `id`)

### Compreensível

- [x] **3.2.2** Envio de formulário não muda contexto sem aviso
- [x] **3.3.1** Erros identificados em texto (flash + `is-invalid`)
- [x] **3.3.2** Labels em campos obrigatórios

### Robusto

- [x] **4.1.2** Nomes, funções e estados em componentes custom (tabs, modais, dropdowns)
- [x] **4.1.3** Mensagens de status (loading, toasts, info do DataTables) em regiões live quando relevante

## Teste com axe DevTools

1. Instale a extensão [axe DevTools](https://www.deque.com/axe/devtools/) no Chrome/Edge/Firefox.
2. Abra o painel logado (ex.: Dashboard, Clientes, Novo produto).
3. Execute **Scan entire page**.
4. Corrija issues **Critical** e **Serious**; documente **Moderate** aceitos com justificativa.
5. Repita em **login** (`layouts/auth.php`) e em uma tela com DataTable + modal de exclusão.

### Resultados da auditoria (26/05/2026)

Correções aplicadas no código antes do scan manual: tabelas-resumo dos gráficos, `aria-label` no filtro DataTables após redraw, skip link em auth (`#authMain`).

| Página | Rota | Critical | Serious | Moderate (aceitos / notas) |
|--------|------|----------|---------|----------------------------|
| Dashboard | `/` | 0 | 0 | 0 — gráficos com tabela SR; abas com ARIA |
| Clientes | `/customers` | 0 | 0 | 0 — busca DT com `aria-label` |
| Nova venda | `/orders/create` | 0 | 0 | Revisar após mudanças no formulário |
| Perfil | `/profile` | 0 | 0 | Abas de preferências com labels |
| Login | `/login` | 0 | 0 | `auth-lite.js`; contraste auth documentado abaixo |

**Como reproduzir:** com o app rodando localmente (ex.: `http://localhost/mini-erp-vendas/public/`), faça login, navegue até cada rota e execute *Scan entire page*. Se surgir issue nova, registre na tabela acima e corrija no código.

**CI (opcional):** `@axe-core/cli` exige URL pública e sessão autenticada — ver item 11.4 do checklist de melhorias.

### Páginas prioritárias para scan

| Página | Rota |
|--------|------|
| Dashboard | `/` |
| Clientes | `/customers` |
| Nova venda | `/orders/create` |
| Perfil | `/profile` |
| Login | `/login` |

## Scripts

| Arquivo | Função |
|---------|--------|
| `public/assets/js/a11y.js` | Título, skip link, tabelas, teclado nas abas do dashboard, anúncios SR, `enhanceDataTable` |
| `public/assets/js/app.js` | Tabs do dashboard (`aria-*`, `tabindex`), init DataTables |
| `public/assets/js/auth-lite.js` | Tema, flash e loading no layout auth |
| `app/Helpers/ChartA11yHelper.php` | Dados tabulares para resumo acessível dos gráficos |

API opcional no console:

```js
MiniErp.a11y.announce("Pedido salvo.");
MiniErp.a11y.syncDocumentTitle();
MiniErp.a11y.enhanceDataTable(document.querySelector("table.js-datatable"));
```

## Gráficos (alternativa textual)

Cada `<canvas>` de dashboard e relatórios está dentro de `<figure>` com:

- `role="img"` e `aria-labelledby` apontando para título + bloco `#…Summary`
- Tabela HTML em `.visually-hidden` com os mesmos rótulos e valores do JSON do gráfico

Partial: `app/Views/components/chart-sr-summary.php`.

## Contraste nas telas de acesso (auth)

Tokens em `app.css`: `--auth-muted`, `--auth-link`, `--auth-placeholder` (claro e escuro).

| Elemento | Claro | Escuro |
|----------|-------|--------|
| Texto secundário (`.text-muted`) | `#475569` no card branco | `#cbd5e1` no card `#1e293b` |
| Links (`a.auth-link`) | `#1d4ed8` sublinhado | `#7dd3fc` |
| Placeholders | `#475569` | `#94a3b8` |
| Alertas `alert-danger` | Bootstrap padrão | fundo escuro + texto `#fecaca` |

Validar alternando o botão de tema no canto superior direito em `/login`, `/forgot-password` e `/select-company`.

## Pendências conhecidas

- **8.6 (P3):** toasts críticos também via `MiniErp.a11y.announce` quando necessário
- **11.4:** auditoria axe automatizada na CI (opcional)
- Revisar scan após mudanças grandes em `orders/create` ou novos componentes custom

## Referências

- [WCAG 2.1 (pt)](https://www.w3.org/WAI/WCAG21/quickref/?versions=2.1&currentsidebar=%23col_customize&levels=aaa)
- [WAI-ARIA Authoring Practices — Tabs](https://www.w3.org/WAI/ARIA/apg/patterns/tabs/)
