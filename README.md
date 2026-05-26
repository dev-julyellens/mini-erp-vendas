# Mini ERP de Vendas

Sistema web em **PHP 8.0+**, arquitetura **MVC**, com **Repositories**, **Services** (regras de negócio) e **PostgreSQL** (PDO). Painel multiempresa com controle de permissões, financeiro, relatórios e API REST.

Documentação técnica completa: [docs/README.md](docs/README.md)

## Requisitos

- PHP **8.0+** (recomendado 8.2+) com extensões: `pdo`, `pdo_pgsql`, `json`, `mbstring`, `gd`
- [Composer](https://getcomposer.org/)
- PostgreSQL 12+
- Apache com `mod_rewrite` (XAMPP) **ou** servidor com *document root* em `public/`

## Instalação rápida

### 1) Dependências

```bash
composer install
```

O repositório inclui `composer.lock` para builds reproduzíveis.

### 2) Configuração

```bash
cp config/.env.example config/.env
```

Edite `config/.env` — principalmente banco, `APP_BASE_URL`, `JWT_SECRET` e e-mail (`MAIL_*`).

| Variável | Descrição |
|----------|-----------|
| `DB_*` | Conexão PostgreSQL |
| `APP_BASE_URL` | URL pública (ex.: `http://localhost/mini-erp-vendas/public`) |
| `APP_DEBUG` | `true` em desenvolvimento |
| `APP_ENV` | `local` ou `production` (com `APP_DEBUG=false` em produção) |
| `JWT_SECRET` | Obrigatório com `APP_DEBUG=false` (mín. 32 caracteres) |
| `MAIL_DRIVER` | `log` (dev), `smtp` ou `mail` — ver [autenticação](docs/arquitetura/autenticacao.md) |

### 3) Banco de dados

**Instalação nova** (schema completo):

```bash
psql -U postgres -d mini_erp_vendas -f database/database.sql
php database/run_migration.php
```

**Banco existente** — apenas migrations pendentes:

```bash
php database/run_migration.php
# ou
php bin/migrate
```

### 4) Apache (XAMPP)

Acesse: `http://localhost/mini-erp-vendas/public/`

Ajuste `RewriteBase` em `public/.htaccess` se o caminho do projeto for diferente.

### Credenciais padrão

Após instalação: `admin@mini-erp.local` / `Admin@123` — **altere no primeiro acesso**.

---

## Desenvolvimento

### Qualidade de código

```bash
composer check
```

Executa, em sequência: verificação UTF-8 sem BOM, PHPStan (nível 5) e PHPUnit.

Comandos individuais:

```bash
composer encoding   # bin/check-encoding.php
composer analyse    # PHPStan
composer test       # PHPUnit
```

### Migrations

```bash
php bin/migrate
```

Ver [docs/arquitetura/banco.md](docs/arquitetura/banco.md) e [docs/arquitetura/devops.md](docs/arquitetura/devops.md).

### CI

GitHub Actions (`.github/workflows/quality.yml`) roda `composer check` em push/PR para `main`/`master`.

---

## Módulos do sistema

| Área | Funcionalidades |
|------|-----------------|
| **Autenticação** | Login, logout, sessão segura, CSRF, recuperação de senha (PHPMailer), seleção de empresa |
| **Permissões** | ACL por módulo/ação; papéis `admin`, `vendedor`, `financeiro`, `estoque` |
| **Cadastros** | Clientes, produtos, serviços, categorias |
| **Comercial** | Vendas com múltiplos itens, cancelamento, histórico de preço em `order_items` |
| **Estoque** | Movimentações, alertas de estoque baixo |
| **Financeiro** | Contas a receber, recebimentos, parcelas, fluxo de caixa |
| **PIX** | Cobranças (gateway mock em dev), webhook, conciliação |
| **Relatórios** | Vendas, estoque, fluxo; export PDF/Excel; gráficos na tela |
| **Dashboard** | KPIs por abas (visão geral, comercial, financeiro, operacional) |
| **Multiempresa** | Empresas, vínculos usuário–empresa, troca de contexto |
| **SaaS** | Planos, assinaturas, onboarding, tela de assinatura |
| **Operação** | Notificações, backup/restore, auditoria, logs de acesso |
| **LGPD** | Consentimento e mascaramento de dados sensíveis |
| **Perfil** | Avatar, preferências de UI (tema, sidebar) |
| **API REST** | JWT, produtos, pedidos, rate limit — ver [docs/arquitetura/api.md](docs/arquitetura/api.md) |

---

## API REST (resumo)

| Método | Rota | Descrição |
|--------|------|-----------|
| `POST` | `/api/auth/login` | Obter token JWT |
| `GET` | `/api/products` | Listar produtos |
| `GET` | `/api/orders` | Listar pedidos (filtros, paginação) |
| `POST` | `/api/orders` | Criar pedido |
| `POST` | `/api/orders/cancel` | Cancelar pedido |

Autenticação: header `Authorization: Bearer <token>` ou sessão web.

---

## Regra de negócio importante (histórico de preço)

Na finalização da venda, o sistema grava em `order_items.unit_price` o preço vigente do produto **no momento da venda**, preservando histórico mesmo se o cadastro mudar depois. Documentado em `App\Services\OrderService`.

---

## Estrutura do projeto

```
/app
  /Controllers
  /Core
  /Helpers
  /Models
  /Repositories
  /Services
  /Views
/bin              # Scripts utilitários (encoding, migrate)
/config           # app.php, .env
/database         # database.sql, migrations/
/docs             # Documentação técnica
/public           # Document root (index.php, assets)
/storage          # logs, backups, avatars
/tests            # PHPUnit
```

---

## UI e acessibilidade

- Design system em `public/assets/css/design-system.css`
- Componentes reutilizáveis: `page-header`, `action-buttons`, `filter-panel`, `kpi-card`
- Checklist de migração UI: [docs/implementacoes/checklist-refatoracao-ui.md](docs/implementacoes/checklist-refatoracao-ui.md)
- Acessibilidade: [docs/implementacoes/acessibilidade.md](docs/implementacoes/acessibilidade.md)

---

## Melhorias e roadmap

Checklist priorizada de evolução: [docs/implementacoes/checklist-melhorias-projeto.md](docs/implementacoes/checklist-melhorias-projeto.md)

---

## Formato de valores

Nos formulários web de produto, use **ponto** como separador decimal (ex.: `19.90`). Máscaras de moeda disponíveis via `data-mask-money` e `input-masks.js`.
