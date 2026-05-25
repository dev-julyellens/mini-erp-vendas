# Implementação: Multiempresa

**Prompt de origem:** `.cursor/prompts/15-multiempresa.md`  
**Data de referência:** maio/2026  
**Escopo:** isolamento de dados por empresa, vínculo usuário↔empresa, seleção no login e filtros automáticos em repositórios.

---

## Visão geral

O sistema passou de **single-tenant** para **multiempresa** com:

- Tabela `companies` e pivot `user_companies`
- Coluna `company_id` em entidades de negócio (clientes, categorias, produtos, pedidos, fluxo de caixa)
- Sessão e JWT com contexto de empresa ativa
- Filtro `company_id` em repositórios via trait `CompanyScope` e helper `CompanyContext`

### Fluxo de login (web)

```
POST /login → valida credenciais
    → 1 empresa: login completo (sessão com company_id)
    → N empresas: pending user → GET /select-company → POST /select-company → dashboard
```

Troca de empresa em sessão: botão no header → `/select-company`.

### Fluxo API

`POST /api/auth/login` aceita `company_id` no JSON. Se o usuário tem apenas uma empresa, ela é escolhida automaticamente. O JWT inclui `company_id` e o middleware valida acesso na pivot.

---

## Banco de dados

| Arquivo | Conteúdo |
|---------|----------|
| `database/migrations/013_create_companies.sql` | Schema multiempresa + migração de dados existentes para empresa id=1 |

### Tabelas novas

- `companies` — cadastro de empresas
- `user_companies` — quais usuários acessam quais empresas

### Colunas `company_id`

- `customers`, `categories`, `products`, `orders`, `cash_flow`, `audit_logs` (opcional)

### Índices únicos por empresa

- `(company_id, LOWER(email))` em clientes
- `(company_id, LOWER(name))` em categorias
- `(company_id, UPPER(sku))` e barcode em produtos

### Seed padrão

Empresa **Empresa Padrão** (id=1); todos os usuários e dados legados vinculados a ela.

```bash
php database/run_migration.php
```

---

## Arquivos principais

| Arquivo | Responsabilidade |
|---------|------------------|
| `app/Helpers/CompanyContext.php` | `company_id` da sessão ou JWT |
| `app/Helpers/Auth.php` | Sessão com `company_id` / `company_name`, pending login |
| `app/Services/CompanyAuthService.php` | Regras de seleção e troca de empresa |
| `app/Services/AuthService.php` | Login em duas etapas quando necessário |
| `app/Repositories/CompanyRepository.php` | Empresas e pivot user_companies |
| `app/Repositories/Concerns/CompanyScope.php` | Trait `companyId()` nos repositórios |
| `app/Controllers/AuthController.php` | Telas login e select-company |
| `app/Views/auth/select-company.php` | Seleção de empresa |
| `app/Middleware/AuthMiddleware.php` | Exige empresa selecionada nas rotas protegidas |
| `app/Services/JwtService.php` | Claim `company_id` no token |

### Repositórios com filtro automático

`CustomerRepository`, `CategoryRepository`, `ProductRepository`, `OrderRepository`, `StockMovementRepository`, `DashboardRepository`, `ReportRepository`, `AccountsReceivableRepository`, `InstallmentRepository`, `PaymentRepository`, `CashFlowRepository`, `AuditRepository` (leitura/escrita quando há contexto).

---

## Segurança e isolamento

- Usuário só vê empresas listadas em `user_companies`
- `findById` / `paginate` / agregações incluem `WHERE company_id = :company_id`
- Pedidos validam cliente e produtos da mesma empresa via repositórios escopados
- API rejeita token sem `company_id` válido ou sem vínculo na pivot

---

## Teste manual sugerido

1. Login `admin@mini-erp.local` / `Admin@123` — deve entrar direto na Empresa Padrão.
2. Inserir segunda empresa e vincular usuário em `user_companies`; login deve exibir seleção.
3. Criar cliente/produto na empresa A; trocar para empresa B — listagens não devem exibir dados de A.
4. API: `POST /api/auth/login` com `company_id: 1` e chamar `GET /api/products` com Bearer.

---

## Compatibilidade

- Dados existentes preservados na empresa padrão (id=1).
- Permissões globais por role (sem `company_id` em ACL).
- Backup continua cobrindo o banco inteiro (infra global).
