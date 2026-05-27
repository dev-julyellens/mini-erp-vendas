# Checklist priorizada — Melhorias do projeto

Checklist consolidada após análise do repositório (maio/2026): código, documentação, segurança, qualidade, frontend e DevOps.

**Relacionados**

| Documento | Conteúdo |
|-----------|----------|
| [checklist-refatoracao-ui.md](checklist-refatoracao-ui.md) | UI/UX — telas, componentes, design system (~95% concluído) |
| [bugs-conhecidos.md](../bugs/bugs-conhecidos.md) | Bugs e riscos já catalogados |
| [debito-tecnico.md](../bugs/debito-tecnico.md) | Débito técnico resumido |
| [acessibilidade.md](acessibilidade.md) | WCAG, axe, `a11y.js` |

**Legenda por tarefa**

| Sigla | Significado |
|-------|-------------|
| **SEC** | Segurança / produção |
| **OPS** | DevOps / CI / deploy |
| **DOC** | Documentação |
| **BE** | Backend (services, repositories, controllers) |
| **API** | API REST / JWT |
| **DB** | Banco / migrations |
| **FE** | Frontend (JS/CSS/assets) |
| **A11Y** | Acessibilidade |
| **TST** | Testes automatizados |
| **INT** | Integração externa (e-mail, PIX real) |

**Status**

| Ícone | Significado |
|-------|-------------|
| ✅ | Concluído ou aceitável para o estágio atual |
| 🟡 | Parcial / mitigado / documentado |
| ⬜ | Pendente |
| 🔴 | Bloqueador para produção séria |

**Prioridade**

| Nível | Quando usar |
|-------|-------------|
| **P0** | Antes de produção ou risco alto imediato |
| **P1** | Alto impacto operacional ou manutenção |
| **P2** | Médio prazo — qualidade e escala |
| **P3** | Polimento / nice-to-have |

---

## Visão geral — o que já está bom

| Área | Situação | Referência |
|------|----------|------------|
| Arquitetura MVC + Services + Repositories | ✅ Sólida | `app/`, `docs/arquitetura/` |
| Multi-tenant (`CompanyScope`) | ✅ | `app/Repositories/Concerns/CompanyScope.php` |
| CSRF, sessão segura, headers HTTP | ✅ | `app/bootstrap.php`, `SecurityHeadersMiddleware` |
| Design system + componentes de view | ✅ ~95% | `checklist-refatoracao-ui.md` |
| Migrations numeradas + runner | ✅ | `database/run_migration.php` |
| Qualidade local (`composer check`) | ✅ Script existe | `composer.json` |
| Documentação interna | ✅ ~53 arquivos | `docs/README.md` |
| Auditoria / ACL / LGPD base | ✅ | `docs/implementacoes/17-lgpd-e-seguranca.md` |

**Progresso estimado global (melhorias pendentes): ~35%** — UI quase fechada; backend, testes, CI e produção ainda com gaps.

---

## Fase 0 — Bloqueadores de produção (P0)

Fazer **antes** de expor o sistema a usuários reais fora de ambiente controlado.

| # | Tarefa | Sigla | Status | Arquivo / ação |
|---|--------|-------|--------|----------------|
| 0.1 | Remover fallback de `JWT_SECRET` em produção (falhar boot se ausente) | SEC | ✅ | `SecurityBootstrap.php`, `AppConfig::jwtSecret()` |
| 0.2 | Garantir `JWT_SECRET` forte no `.env` de cada ambiente | SEC | ✅ | `config/.env.example` (mín. 32 chars) |
| 0.3 | Desabilitar webhook PIX mock fora de dev (`APP_DEBUG` + secret obrigatório) | SEC | ✅ | `MockPixGateway`, `PixWebhookController`, middlewares |
| 0.4 | Nunca usar `APP_DEBUG=true` em produção | SEC | ✅ | `SecurityBootstrap` + `APP_ENV=production` |
| 0.5 | Implementar envio de e-mail no reset de senha | INT | ✅ | `MailService` (PHPMailer) + `AuthService` (log/smtp/mail) |
| 0.6 | Revisar restauração de backup (confirmação + permissão + log) | SEC | 🟡 | `BackupController`, `BackupService` — já tem `RESTAURAR`; auditar quem pode executar |
| 0.7 | Versionar `composer.lock` ou documentar política explícita de build | OPS | ✅ | `composer.lock` + README/devops |
| 0.8 | Adicionar `/storage/avatars/*` ao `.gitignore` | OPS | ✅ | `.gitignore` + `storage/avatars/.gitkeep` |

**Critério de aceite Fase 0:** deploy em staging sem secrets padrão; reset de senha funcional; webhook mock inacessível em produção.

---

## Fase 1 — DevOps e documentação (P0–P1)

| # | Tarefa | Sigla | Status | Arquivo / ação |
|---|--------|-------|--------|----------------|
| 1.1 | Criar workflow CI (GitHub Actions ou equivalente) | OPS | ✅ | `.github/workflows/quality.yml` |
| 1.2 | Pipeline: `composer encoding` + `composer analyse` + `composer test` | OPS | ✅ | `composer check` no workflow |
| 1.3 | Atualizar `README.md` — PHP **8.0+** (não 7.4) | DOC | ✅ | `README.md` |
| 1.4 | README — listar módulos reais (financeiro, PIX, relatórios, SaaS, LGPD, backup…) | DOC | ✅ | `README.md` |
| 1.5 | README — seção Desenvolvimento (`composer install`, `composer check`, migrations) | DOC | ✅ | `README.md` |
| 1.6 | README — link para `docs/README.md` | DOC | ✅ | `README.md` |
| 1.7 | Criar `docs/arquitetura/devops.md` (CI, env, deploy, HSTS) | DOC | ✅ | `docs/arquitetura/devops.md` |
| 1.8 | Atualizar `docs/arquitetura/frontend.md` (design system e a11y já existem) | DOC | ✅ | Seções 9–14 |
| 1.9 | Script `bin/migrate` → wrapper de `database/run_migration.php` | OPS | ✅ | `bin/migrate` + `composer migrate` |

**Critério de aceite Fase 1:** todo PR roda `composer check`; README reflete o produto atual.

---

## Fase 2 — Banco de dados e migrations (P1)

| # | Tarefa | Sigla | Status | Arquivo / ação |
|---|--------|-------|--------|----------------|
| 2.1 | Bootstrap de migrations **018–020** no `run_migration.php` | DB | ✅ | Detectors 018 (`pix_cobranca`), 019 (`role`), 020 (`user_preferences`) |
| 2.2 | Regenerar ou documentar processo de sync `database.sql` ↔ migrations | DB | ✅ | `docs/arquitetura/banco.md` §12 |
| 2.3 | Avaliar `stock_movements` sem `company_id` (join obrigatório) | DB | 🟡 | `docs/bugs/debito-tecnico.md` |
| 2.4 | Revisar índices em listagens pesadas (parcelas, auditoria) | DB | ⬜ | Repositories + `EXPLAIN` |

**Detalhe 2.1:** hoje `bootstrapAppliedMigrations()` para em `017_create_saas.sql`; bancos legados podem reaplicar 018–020 incorretamente ou falhar em upgrade.

---

## Fase 3 — Backend e arquitetura (P1–P2)

| # | Tarefa | Sigla | Status | Arquivo / ação |
|---|--------|-------|--------|----------------|
| 3.1 | Padronizar mensagens de domínio em **português** (ou camada i18n) | BE | 🟡 | Pedidos, clientes, cancelamento, API; outros services pendentes |
| 3.2 | Alinhar status de pedido (`pending` vs `paid` na criação) | BE | ✅ | `OrderService::STATUS_PAID`; doc em `bugs-conhecidos.md` |
| 3.3 | Adotar `Database::transaction()` nos services com transação manual | BE | ✅ | `OrderService`, `PaymentService`, `OrderCancelService`, `InstallmentService::pay`, `PixChargeService::reconcile` |
| 3.4 | Remover side-effect em leitura: `refreshOverdueStatuses()` no `paginateFiltered` | BE | ✅ | Chamada explícita em `InstallmentService::search` e notificações |
| 3.5 | Migrar repositórios para `BaseRepository` + PDO injetável | BE | 🟡 | `InstallmentRepository` migrado; demais pendentes |
| 3.6 | Reduzir duplicação Product / Service / Category controllers | BE | P2 | Controllers CRUD similares |
| 3.7 | Política ACL documentada: opt-in no `RoutePermissionMap` vs default deny | BE | ✅ | `docs/arquitetura/permissoes.md` §9 + checklist PR |
| 3.8 | Auditar **toda** rota nova no mapa de permissões | BE | ⬜ | Checklist em PR |
| 3.9 | Hub `/reports` — decidir se índice exige permissão ou mantém filtro por card | BE | 🟡 | `ReportController`, bugs conhecidos |
| 3.10 | Logging estruturado em operações críticas (não só exceções globais) | BE | ✅ | Vendas, recebimentos, parcelas, PIX webhook, backup |
| 3.11 | Router com parâmetros `{id}` e métodos HTTP adicionais (futuro) | BE | P3 | `app/Core/Router.php` |

---

## Fase 4 — API REST e JWT (P1–P2)

| # | Tarefa | Sigla | Status | Arquivo / ação |
|---|--------|-------|--------|----------------|
| 4.1 | DTO público de produto **sem** `cost_price`, margem, markup | API | ✅ | `ApiProductPresenter` |
| 4.2 | Paginação em `GET /api/products` | API | ✅ | `ProductRepository::paginate()` + `meta` |
| 4.3 | Eliminar N+1 em `GET /api/orders` (itens em batch/JOIN) | API | ✅ | `OrderItemRepository::findByOrderIds()` |
| 4.4 | Validar formato de `date_from` / `date_to` na API | API | ✅ | `DateFilter` + intervalo |
| 4.5 | Rate limit por token/usuário além de IP | API | P2 | `ApiRateLimitService` |
| 4.6 | Revogação / rotação de JWT (ou TTL curto + refresh) | API | P2 | `JwtService` |
| 4.7 | Evitar `Auth::setJwtUser()` poluir sessão de browser no mesmo domínio | API | P2 | `Auth.php` |
| 4.8 | Log local em `catch` dos controllers API (hoje só 500 genérico) | API | 🟡 | `ApiOrderController` (index/store/cancel); `ApiAuthController` já tinha log |

---

## Fase 5 — Testes e qualidade estática (P1–P2)

| # | Tarefa | Sigla | Status | Arquivo / ação |
|---|--------|-------|--------|----------------|
| 5.1 | Testes unitários existentes | TST | ✅ | `tests/Unit/` — Validator, Money, ValidationException |
| 5.2 | Testes de `OrderService` (estoque, transação, preço histórico) | TST | ✅ | `OrderServiceTest` + `OrderServiceIntegrationTest` |
| 5.3 | Testes de isolamento multi-tenant | TST | ✅ | `TenantIsolationTest` |
| 5.4 | Testes de API auth + permissões | TST | ✅ | `JwtServiceTest`, `ApiAuthServiceTest`, `ApiAuthServiceIntegrationTest` |
| 5.5 | Testes de `AuthService` / reset de senha | TST | ⬜ | Após e-mail implementado |
| 5.6 | Reduzir `ignoreErrors` no PHPStan (controllers) | TST | ✅ | Removido ignore `SubscriptionRepository::markPastDue` |
| 5.7 | Subir nível PHPStan gradualmente (5 → 6) | TST | 🟡 | Nível 6: 14 avisos de `array` sem value-type — corrigir em lote |
| 5.8 | Opcional: PHP-CS-Fixer PSR-12 | TST | P3 | `require-dev` |
| 5.9 | Atualizar PHP mínimo para **8.2+** (8.0 EOL) | OPS | P2 | `composer.json` |

**Cobertura atual:** ~3 arquivos de teste — gap crítico para regressão.

---

## Fase 6 — Frontend e performance (P1–P2)

| # | Tarefa | Sigla | Status | Arquivo / ação |
|---|--------|-------|--------|----------------|
| 6.1 | Carregar jQuery + DataTables **só** em páginas com tabela datatable | FE | ✅ | `View.php` detecta `js-datatable`; `layouts/main.php` |
| 6.2 | `auth-lite.js` no layout auth (tema + flash + loading, sem sidebar/DataTables) | FE | ✅ | `layouts/auth.php`, `public/assets/js/auth-lite.js` |
| 6.3 | Unificar `product_form.js` e `service_form.js` (margens/moeda) | FE | ⬜ | `form-margins.js` ou módulo compartilhado |
| 6.4 | Cache-busting em assets (`?v=` ou hash de build) | FE | ✅ | `App\Helpers\Asset::versionedUrl()` em `main.php` / `auth.php` |
| 6.5 | SRI em CDNs DataTables e Chart.js | FE | P2 | `main.php`, controllers de relatório |
| 6.6 | Dividir `app.js` monolítico (~930 linhas) por domínio | FE | P2 | sidebar, prefs, datatables, toasts |
| 6.7 | Headers de cache estáticos no Apache | FE | P3 | `public/.htaccess` |
| 6.8 | Polimento: botões crus em `reports/index.php` | FE | P3 | `reports/index.php` |

---

## Fase 7 — Segurança HTTP e CSP (P1–P2)

| # | Tarefa | Sigla | Status | Arquivo / ação |
|---|--------|-------|--------|----------------|
| 7.1 | Headers base (nosniff, X-Frame-Options, Referrer-Policy, CSP) | SEC | ✅ | `SecurityHeadersMiddleware.php` |
| 7.2 | HSTS condicional em produção HTTPS | SEC | ✅ | `SecurityHeadersMiddleware` + `AppConfig::isProduction()` |
| 7.3 | Plano para remover `'unsafe-inline'` da CSP | SEC | 🟡 | `theme-boot.js` (scripts); `style-src` inline pendente |
| 7.4 | `upgrade-insecure-requests` em produção | SEC | P2 | CSP |
| 7.5 | Restringir simulação PIX em produção com gateway real | SEC | 🟡 | `PIX_DEFAULT_GATEWAY`, `MockPixGateway` |

---

## Fase 8 — Acessibilidade (P1)

| # | Tarefa | Sigla | Status | Arquivo / ação |
|---|--------|-------|--------|----------------|
| 8.1 | Infra a11y (skip link, `a11y.js`, ARIA tabs, live regions) | A11Y | ✅ | Ver `acessibilidade.md` |
| 8.2 | Preencher checklist WCAG manual (seção Perceptível → Robusto) | A11Y | ✅ | `acessibilidade.md` — validado 26/05/2026 |
| 8.3 | Rodar axe DevTools nas 5 páginas prioritárias e registrar resultados | A11Y | ✅ | Tabela em `acessibilidade.md`; scan manual recomendado após deploy |
| 8.4 | Alternativa textual para gráficos Chart.js | A11Y | ✅ | `ChartA11yHelper`, `chart-sr-summary.php`, dashboard + relatórios |
| 8.5 | Reaplicar `aria-label` em DataTables após redraw | A11Y | ✅ | `MiniErp.a11y.enhanceDataTable` + evento `draw` |
| 8.6 | Toasts críticos via `MiniErp.a11y.announce` quando necessário | A11Y | P3 | `app.js` `showToast` |

**Meta:** subir barra de “~30% a11y” do checklist UI para **≥80%** com auditoria documentada.

---

## Fase 9 — Menu e navegação (P2–P3)

Itens do [gerenciamento-completo.md](../../.cursor/prompts/gerenciamento-completo.md) ainda não refletidos em `NavigationMenu.php`:

| Item sugerido | Status | Ação |
|---------------|--------|------|
| Pedidos vs Orçamentos | ✅ | Orçamentos em `quotes` / `quote_items`; conversão gera `orders` |
| Recebimentos | ✅ | Menu → `finance/accounts-receivable` |
| Histórico financeiro | ✅ | Menu → `finance/installments/history` |
| Inventário | 🟡 | Futuro — ver `debito-tecnico.md` |
| Permissões (menu Admin) | ✅ | `profile#permissoes` |
| Reestruturação em grupos (Dashboard, Comercial, Financeiro…) | ✅ | `NavigationMenu.php` (grupos existentes + itens financeiros) |

Ver também seção **Menu** em [checklist-refatoracao-ui.md](checklist-refatoracao-ui.md).

---

## Fase 10 — Integrações e funcionalidades de negócio (P1–P2)

| # | Tarefa | Sigla | Status | Arquivo / ação |
|---|--------|-------|--------|----------------|
| 10.1 | Gateway PIX real (`PixGatewayInterface`) | INT | ✅ | `MercadoPagoPixGateway` + webhook `/webhooks/pix/mercadopago` |
| 10.2 | E-mail transacional (reset, notificações críticas) | INT | ✅ | PHPMailer + `MailService::sendTransactional`; `.env.example` |
| 10.3 | Módulo de orçamentos (se escopo de produto) | BE | ✅ | `quotes`, `QuoteService`, UI `/quotes` |
| 10.4 | Inventário físico (contagem) | BE | ✅ | `inventory_counts`, ajuste tipo `inventario`, UI `/inventory` |

---

## Fase 11 — UI/UX remanescente (P3)

Maioria concluída — ver [checklist-refatoracao-ui.md](checklist-refatoracao-ui.md).

| # | Tarefa | Status | Notas |
|---|--------|--------|-------|
| 11.1 | Listagens e formulários CRUD padronizados | ✅ | ~98% |
| 11.2 | Relatórios na tela (KPI + Chart.js) | ✅ | 5 relatórios principais |
| 11.3 | Contraste auth claro/escuro | ✅ | `--auth-*`, `auth-link` |
| 11.4 | Auditoria axe automatizada na CI | ⬜ | Opcional — `@axe-core/cli` ou manual documentado |
| 11.5 | Itens de menu pendentes | ⬜ | Fase 9 acima |

---

## Ordem de execução recomendada (sprints)

### Sprint A — Produção segura (2–3 dias) — P0 ✅

1. ~~0.1–0.4 — JWT e webhook PIX mock~~  
2. ~~0.5 — E-mail de reset de senha~~  
3. ~~0.8 — `.gitignore` avatars~~  
4. ~~1.1–1.2 — CI com `composer check`~~  
### Sprint B — Documentação e DX (1 dia) — P0–P1 ✅

1. ~~1.3–1.6 — README completo~~  
2. ~~0.7 — `composer.lock`~~  
3. ~~1.7–1.8 — docs devops e frontend~~  
4. ~~2.1 — bootstrap migrations 018–020~~  
5. ~~2.2 — documentar sync `database.sql`~~  
6. ~~1.9 — `bin/migrate` + `composer migrate`~~

### Sprint C — API e dados sensíveis (1–2 dias) — P1 ✅

1. ~~4.1–4.4 — API produtos/pedidos~~  
2. ~~3.2 — status de pedido~~  
3. ~~3.1 — mensagens PT (pedidos, clientes, cancelamento, API)~~  
4. ~~4.8 parcial — log em `ApiOrderController`~~

### Sprint D — Testes mínimos (2–3 dias) — P1 ✅

1. ~~5.2 — `OrderService`~~  
2. ~~5.3 — tenant isolation~~  
3. ~~5.4 — API auth~~  
4. ~~CI integração — `.github/workflows/integration.yml` + `composer test:integration`~~

### Sprint E — Performance frontend (1–2 dias) — P1 ✅

1. ~~6.1 — DataTables condicional~~  
2. ~~6.2 — auth-lite~~  
3. ~~6.4 — cache-busting~~

### Sprint F — Acessibilidade formal (1 dia) — P1 ✅

1. ~~8.2–8.3 — checklist + axe nas 5 páginas~~  
2. ~~8.4 — gráficos (tabela resumo + `figure`)~~  
3. ~~8.5 — DataTables após redraw~~

### Sprint G — Backend qualidade (contínuo) — P2 ✅

1. ~~3.3–3.5 — transações, repositórios, installments~~  
2. ~~3.10 — logging~~  
3. ~~5.6 — PHPStan ignore~~ · 5.7 🟡 (nível 6 mapeado)  
4. ~~7.2 — HSTS~~ · 7.3 🟡 (`theme-boot.js`)

### Sprint H — Menu e integrações (conforme produto) — P2–P3 ✅

1. ~~Fase 9 — `NavigationMenu.php`~~  
2. ~~10.1 — Mercado Pago PIX~~  
3. ~~10.2 — e-mail transacional documentado + `sendTransactional`~~

---

## Matriz de progresso por área

| Área | Concluído (aprox.) | Próximo passo |
|------|-------------------|---------------|
| UI / design system | ~95% | Menu + polimento botões |
| Segurança produção | ~60% | JWT, webhook, e-mail reset |
| DevOps / CI | ~10% | Workflow `composer check` |
| Documentação externa (README) | ~90% | README + devops + banco §12 |
| Testes automatizados | ~45% | 30 testes (unit + integration); CI integration com PostgreSQL |
| API REST | ~85% | DTO, paginação, N+1, datas; rate limit por token pendente |
| Backend refatoração | ~65% | Migrar mais repos para `BaseRepository`; PHPStan 6 |
| Migrations / DB | ~92% | Bootstrap 018–020; índices pendentes (2.4) |
| Performance JS | ~75% | Unificar form margens (6.3); SRI CDNs (6.5) |
| Acessibilidade auditada | ~80% | CI axe (11.4); toasts SR (8.6) |
| Integrações (PIX/e-mail) | ~75% | Credenciais MP em produção; orçamentos e inventário implementados |

---

## Template rápido — item novo no checklist

Ao descobrir um gap:

```markdown
| # | Tarefa | Sigla | Status | Arquivo / ação |
|---|--------|-------|--------|----------------|
| X.Y | Descrição objetiva | BE/API/… | ⬜ | `caminho/arquivo.php` — nota |
```

Atualizar também `docs/bugs/bugs-conhecidos.md` ou `debito-tecnico.md` se for bug estrutural.

---

## Como manter este documento

1. Após cada sprint, marcar ✅ e data na linha.  
2. Itens resolvidos só em UI → atualizar `checklist-refatoracao-ui.md`, não duplicar aqui.  
3. Novos bugs → `docs/bugs/bugs-conhecidos.md` + referência cruzada aqui.  
4. Antes de release: Fase 0 deve estar ✅ ou explicitamente aceita com risco documentado.

**Última atualização:** 26/05/2026 — Sprint H: Mercado Pago PIX, menu financeiro, e-mail transacional.
