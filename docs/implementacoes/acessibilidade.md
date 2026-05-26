# Acessibilidade (WCAG 2.1 — nível AA alvo)

Revisão do Sprint 6, item 5: melhorias no layout, componentes e scripts, com checklist manual alinhado às diretrizes WCAG 2.1.

## O que foi implementado

### Navegação e estrutura

| Recurso | Onde |
|--------|------|
| Skip link (“Ir para o conteúdo principal”) | `layouts/main.php`, `layouts/auth.php` |
| `<main id="mainContent" tabindex="-1">` | `layouts/main.php` — destino do skip link |
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

### Formulários

- Labels visíveis nos filtros (padrão `filter-panel`)
- Máscaras não removem `name`/`id` dos campos
- Erros de validação: classes `is-invalid` + texto de erro (Bootstrap)

## Checklist WCAG (manual)

Use este roteiro após mudanças de UI. Marque ✅ quando ok na amostra testada.

### Perceptível

- [ ] **1.1.1** Imagens/ícones decorativos com `aria-hidden="true"` quando não transmitem informação
- [ ] **1.3.1** Cabeçalhos em ordem lógica (`h1` por página via `page-header`)
- [ ] **1.4.3** Contraste texto ≥ 4.5:1 (tema claro e escuro) — ver item 6 do checklist (auth)
- [ ] **1.4.11** Contraste de componentes UI (bordas, botões secundários)

### Operável

- [ ] **2.1.1** Todas as ações principais acessíveis só com teclado
- [ ] **2.4.1** Skip link visível ao focar
- [ ] **2.4.2** Título da página reflete o conteúdo (`a11y.js`)
- [ ] **2.4.3** Ordem de foco coerente (sidebar → topbar → conteúdo)
- [ ] **2.4.4** Links com texto ou `aria-label` compreensível
- [ ] **2.4.6** Rótulos de formulário associados (`for` / `id`)

### Compreensível

- [ ] **3.2.2** Envio de formulário não muda contexto sem aviso
- [ ] **3.3.1** Erros identificados em texto (flash + `is-invalid`)
- [ ] **3.3.2** Labels em campos obrigatórios

### Robusto

- [ ] **4.1.2** Nomes, funções e estados em componentes custom (tabs, modais, dropdowns)
- [ ] **4.1.3** Mensagens de status (loading, toasts) em regiões live quando relevante

## Teste com axe DevTools

1. Instale a extensão [axe DevTools](https://www.deque.com/axe/devtools/) no Chrome/Edge/Firefox.
2. Abra o painel logado (ex.: Dashboard, Clientes, Novo produto).
3. Execute **Scan entire page**.
4. Corrija issues **Critical** e **Serious**; documente **Moderate** aceitos com justificativa.
5. Repita em **login** (`layouts/auth.php`) e em uma tela com DataTable + modal de exclusão.

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
| `public/assets/js/a11y.js` | Título, skip link, tabelas, teclado nas abas do dashboard, anúncios SR |
| `public/assets/js/app.js` | Tabs do dashboard (`aria-*`, `tabindex`) |

API opcional no console:

```js
MiniErp.a11y.announce("Pedido salvo.");
MiniErp.a11y.syncDocumentTitle();
```

## Pendências conhecidas

- Contraste fino em `layouts/auth.php` (Sprint 6, item 6)
- DataTables gerados dinamicamente: revisar cabeçalhos após redraw se adicionar colunas custom
- Gráficos Chart.js: considerar tabela/resumo alternativo para leitores de tela (relatórios)

## Referências

- [WCAG 2.1 (pt)](https://www.w3.org/WAI/WCAG21/quickref/?versions=2.1&currentsidebar=%23col_customize&levels=aaa)
- [WAI-ARIA Authoring Practices — Tabs](https://www.w3.org/WAI/ARIA/apg/patterns/tabs/)
