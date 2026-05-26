# Frontend (Views e assets)

## 1. Visão geral do módulo

Interface **server-side rendering** com PHP puro (sem framework JS). Views em `app/Views/`, assets estáticos em `public/assets/`. Layout responsivo com CSS customizado e JavaScript por tela para formulários dinâmicos.

## 2. Fluxo funcional

1. Controller chama `$this->view('caminho/arquivo', $data)`.
2. `App\Core\View` renderiza PHP com extract de variáveis.
3. Layout `main.php` ou `auth.php` envolve o conteúdo.
4. Partials incluem flash, CSRF, paginação, modal de confirmação, sino de notificações.
5. Assets CSS/JS servidos de `public/assets/`.

## 3. Estrutura de banco relacionada

O frontend não acessa o banco diretamente. Dados vêm de controllers que consultam services/repositories.

## 4. Services envolvidos

Indiretamente todos os services dos módulos exibidos. Helpers de apresentação:

- `App\Helpers\Flash` — mensagens de sessão
- `App\Helpers\Csrf` — token em formulários
- `App\Helpers\Permission` — exibição condicional de ações
- `App\Helpers\DataMask` — mascaramento LGPD em listagens

## 5. Repositories envolvidos

Nenhum acesso direto nas views (padrão MVC respeitado).

## 6. Controllers envolvidos

Todos os controllers web renderizam views. Principais pastas de view:

| Pasta | Módulo |
|-------|--------|
| `dashboard/` | Painel `/` |
| `auth/` | Login, seleção de empresa, reset |
| `customers/`, `products/`, `services/`, `categories/` | Cadastros |
| `orders/` | Vendas |
| `stock/` | Movimentações |
| `finance/` | AR, parcelas, PIX, fluxo |
| `reports/` | Relatórios e export |
| `notifications/` | Central de alertas |
| `backups/` | Backup/restore |
| `audit/`, `access-logs/` | Logs |
| `lgpd/`, `onboarding/`, `subscription/` | Compliance e SaaS |

## 7. Regras de negócio

- Formulários POST incluem `@include` de `partials/csrf.php`.
- Ações destrutivas usam `confirm-modal.php`.
- Permissões na UI espelham ACL (`Permission::can()`), mas **autorização real** é no `PermissionMiddleware`.
- Export de relatórios: links GET para rotas `/export` (Excel/PDF via `ReportExportService`).
- Idioma da interface: **português**; mensagens de erro de services podem vir em inglês.

## 8. Fluxo de dados

```
Browser → public/index.php → Controller@action
       → View (HTML) + Flash
       → assets/css/app.css, assets/js/*.js
```

`APP_BASE_URL` em `.env` afeta links absolutos (`PathHelper`).

## 9. Design system e componentes

Implementado em `public/assets/css/design-system.css` + `app.css` (tokens, tema claro/escuro).

| Componente | Arquivo |
|------------|---------|
| Cabeçalho de página | `app/Views/components/page-header.php` |
| Botões de ação | `app/Views/components/action-buttons.php` |
| Painel de filtros | `app/Views/components/filter-panel.php` |
| KPI | `app/Views/components/kpi-card.php` |
| Auth (título) | `app/Views/components/auth-form-header.php` |

Documentação de botões: [../implementacoes/botoes-padrao.md](../implementacoes/botoes-padrao.md). Checklist de migração: [../implementacoes/checklist-refatoracao-ui.md](../implementacoes/checklist-refatoracao-ui.md).

## 10. JavaScript por tela

| Arquivo | Uso |
|---------|-----|
| `app.js` | Sidebar, tema, DataTables, toasts, prefs |
| `a11y.js` | Skip link, título dinâmico, abas, live regions |
| `input-masks.js` | Máscaras globais (telefone, CEP, moeda) |
| `order_create.js` / `autosave.js` | Nova venda |
| `product_form.js` / `service_form.js` | Margens e preço |
| `dashboard.js` / `reports-charts.js` | Chart.js |

Layouts: `layouts/main.php` (painel), `layouts/auth.php` (login/reset).

## 11. Acessibilidade

- Skip link, `#mainContent`, `lang="pt-BR"`
- ARIA em abas do dashboard e modais
- Checklist e auditoria: [../implementacoes/acessibilidade.md](../implementacoes/acessibilidade.md)

## 12. Pontos críticos

- Sem build step (Webpack/Vite) — JS vanilla por arquivo.
- jQuery + DataTables carregados globalmente no layout principal (melhoria pendente: carregar só onde há tabela).
- `APP_BASE_URL` em `.env` afeta links (`PathHelper`).

## 13. Dependências

- `public/.htaccess` — rewrite para `index.php`
- `SecurityHeadersMiddleware` (CSP, etc.)
- Dompdf / PhpSpreadsheet — export server-side

## 14. Melhorias futuras (pendentes)

- DataTables/jQuery condicionais; `auth-lite.js` no layout auth
- Cache-busting em assets (`?v=`)
- Unificar `product_form.js` e `service_form.js`
- i18n formal para mensagens de backend em inglês
- Auditoria axe automatizada na CI
