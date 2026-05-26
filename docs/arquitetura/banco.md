# Banco de dados

## 1. Visão geral do módulo

O Mini ERP usa **PostgreSQL** como SGBD. O schema é versionado por migrations incrementais em `database/migrations/` e consolidado em `database/database.sql` para instalação completa. O runner `database/run_migration.php` registra execuções em `schema_migrations`.

Multi-tenant: a maioria das tabelas de negócio possui `company_id` (migration `013_create_companies.sql`). O contexto ativo vem de `CompanyContext::requireId()` na sessão.

## 2. Fluxo funcional

1. Desenvolvedor altera ou cria migration SQL numerada.
2. Executa `php database/run_migration.php` (ou aplica `database.sql` em ambiente novo).
3. A aplicação acessa dados via PDO singleton (`App\Core\Database`).
4. Repositórios executam queries parametrizadas; transações em services críticos.

## 3. Estrutura de banco relacionada

### Domínio principal

| Tabela | Propósito |
|--------|-----------|
| `companies` | Empresas (tenant) |
| `users` | Usuários do sistema |
| `user_companies` | Vínculo N:N usuário ↔ empresa |
| `customers` | Clientes por empresa |
| `categories` | Categorias de produtos |
| `products` | Produtos e serviços (`type`: product/service) |
| `orders` / `order_items` | Vendas |
| `stock_movements` | Histórico de movimentação de estoque |
| `accounts_receivable` | Contas a receber |
| `payments` | Recebimentos |
| `installments` | Parcelas |
| `cash_flow` | Fluxo de caixa |
| `pix_charges` | Cobranças PIX |

### Segurança e operação

| Tabela | Propósito |
|--------|-----------|
| `permissions` / `role_permissions` | ACL por papel |
| `audit_logs` | Auditoria (JSONB) |
| `access_logs` | Log de acessos web |
| `api_logs` / `api_rate_limit_buckets` | API |
| `notifications` | Alertas in-app |
| `lgpd_consents` | Consentimento LGPD |
| `backup_settings` / `backup_logs` | Backup |
| `password_reset_tokens` | Reset de senha |

### SaaS

| Tabela | Propósito |
|--------|-----------|
| `plans` / `plan_limits` | Planos e limites |
| `subscriptions` / `subscription_invoices` | Assinatura e faturas |

### Migrations (ordem)

`001` users → … → `017` SaaS → `018` audit constraints → `019` user_companies roles → `020` avatar e user_preferences.

### 12. Sincronização `database.sql`

- **`database/database.sql`**: snapshot para instalação nova; pode ficar atrás das migrations mais recentes.
- **`database/migrations/*.sql`**: fonte da verdade para evolução incremental.
- **Processo recomendado** após criar migration:
  1. Aplicar em banco de teste: `php bin/migrate`
  2. Validar schema
  3. (Opcional) Regenerar `database.sql` via `pg_dump --schema-only` + dados seed, ou documentar que novas instalações devem rodar `run_migration.php` após o dump
- **Bancos legados** sem `schema_migrations`: o runner executa `bootstrapAppliedMigrations()` na primeira vez e marca 001–020 já presentes antes de aplicar pendentes.

## 4. Services envolvidos

Services não acessam SQL diretamente; delegam a repositories. Transações coordenadas em:

- `OrderService`, `OrderCancelService`
- `StockService`, `PaymentService`, `InstallmentService`
- `AccountsReceivableService`, `PixChargeService`
- `BackupService`, `SubscriptionBillingService`

## 5. Repositories envolvidos

Todos em `app/Repositories/` (31 classes). Escopo multi-empresa via trait `CompanyScope` onde aplicável.

Único repositório que estende `BaseRepository` hoje: `CustomerRepository`.

## 6. Controllers envolvidos

Nenhum controller acessa o banco diretamente. Instalação/migrations são scripts CLI, não rotas HTTP.

## 7. Regras de negócio

- FKs e CHECK constraints no schema (ex.: `permissions.module`, `products.type`).
- Estoque em `products.stock` é derivado de movimentações via `StockService`.
- Preço histórico de venda em `order_items.unit_price` (não retroage alterações no catálogo).
- `stock_movements` não tem `company_id`; isolamento via join em `products.company_id`.

## 8. Fluxo de dados

```
HTTP → Controller → Service → Repository → PostgreSQL
                              ↓
                         PDO (Database::getConnection)
```

Valores monetários: strings decimais normalizadas por `App\Helpers\Money` (BCMath).

## 9. Pontos críticos

- **Ordem das migrations** obrigatória em bases existentes.
- **`database.sql`** pode estar desatualizado em relação a migrations recentes se não for regenerado; PIX (`016`) e SaaS (`017`) exigem migrations após dump parcial.
- Transações aninhadas: `Database::transaction()` suporta callbacks aninhados; muitos services ainda usam `beginTransaction` manual.
- Lock pessimista em venda: `ProductRepository::findById($id, true)` com `FOR UPDATE`.

## 10. Dependências

- Extensão PHP `pdo_pgsql`
- Ferramentas externas para backup: `pg_dump`, `psql` (configuráveis em `config/app.php`)
- Variáveis: `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASSWORD` em `config/.env`

## 11. Possíveis melhorias futuras

- Migrar todos os repositories para `BaseRepository` com PDO injetável.
- Adotar `Database::transaction()` de forma uniforme nos services.
- Gerar `database.sql` automaticamente a partir das migrations.
- Índices adicionais conforme volume de dados em produção.
- Considerar `company_id` em `stock_movements` para simplificar queries (hoje via join).
