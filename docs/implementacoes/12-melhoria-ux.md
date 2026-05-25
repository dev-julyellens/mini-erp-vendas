# Melhoria de UX/UI

## Escopo

Aprimoramento visual e de interação sem alterar regras de negócio nem remover funcionalidades.

## Entregas

- **Tema escuro** — toggle no topbar (painel) e na tela de login; preferência em `localStorage` (`mini-erp-theme`).
- **Notificações** — mensagens flash convertidas em toasts Bootstrap; fallback em alertas para ausência de JS.
- **Confirmação visual** — modal Bootstrap substitui `confirm()` nativo em exclusões (clientes, produtos, serviços, categorias).
- **Loading** — spinner no botão de submit e overlay global em formulários.
- **DataTables** — busca e ordenação na página atual; paginação do servidor mantida.
- **Responsividade** — menu lateral retrátil em telas &lt; 992px com backdrop.
- **Paginação** — resumo “Exibindo X–Y de Z” e elipses entre páginas distantes.
- **Filtros** — painéis `filter-panel` nas listagens com filtros GET existentes.

## Arquivos principais

| Arquivo | Papel |
|---------|--------|
| `public/assets/css/app.css` | Variáveis de tema, dark mode, tabelas, loading |
| `public/assets/js/app.js` | Theme, toasts, confirm, DataTables, sidebar |
| `app/Views/layouts/main.php` | CDN DataTables, controles UX |
| `app/Views/partials/confirm-modal.php` | Modal de confirmação |
| `app/Views/partials/pagination.php` | Paginação aprimorada |
| `app/Views/partials/flash.php` | Flash + toasts |

## Uso em novas telas

- Tabela listável: `class="... js-datatable"` e, se houver coluna de ações, `data-dt-actions-col="N"` (índice 0-based).
- Exclusão: `data-confirm="mensagem"` e `data-confirm-title="título"` no `<form>`.
- Formulário sem overlay: `data-global-loading="false"`.
- Botão com texto de carregamento: `data-loading-text="Salvando..."`.
