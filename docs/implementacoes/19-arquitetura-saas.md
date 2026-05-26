# Implementação: Arquitetura SaaS

**Prompt de origem:** `.cursor/prompts/19-arquitetura-SaaS.md`  
**Data de referência:** maio/2026  
**Escopo:** multi-tenant, planos, assinatura, cobrança recorrente da plataforma, limites por plano e onboarding.

---

## Visão geral

O ERP já possuía **multiempresa** (`companies` + `company_id`). Esta entrega adiciona a **camada de produto SaaS** sem remover funcionalidades existentes:

| Recurso | Implementação |
|---------|----------------|
| Multi-tenant | `companies` = tenant; isolamento via `CompanyContext` + `CompanyScope` (existente) |
| Planos | Tabela `plans` + `plan_limits` |
| Assinatura | Tabela `subscriptions` (1 por empresa) |
| Cobrança recorrente | `subscription_invoices` + `SubscriptionBillingService` |
| Limites | `PlanLimitService` em criação de clientes/produtos |
| Onboarding | Fluxo `/onboarding` → `/onboarding/plan` |

O módulo **PIX** continua sendo cobrança de **clientes do ERP** (contas a receber), separado da cobrança **da plataforma** (SaaS).

---

## Banco de dados

| Arquivo | Conteúdo |
|---------|----------|
| `database/migrations/017_create_saas.sql` | Schema SaaS + seed de planos + assinatura enterprise para empresas existentes |
| `database/database.sql` | Schema consolidado atualizado |

### Tabelas novas

- `plans` — catálogo de planos (Starter, Professional, Enterprise)
- `plan_limits` — limites por plano (`customers_max`, `products_max`, `users_max`, `orders_month_max`; `-1` = ilimitado)
- `subscriptions` — assinatura ativa por `company_id`
- `subscription_invoices` — faturas recorrentes da plataforma

### Colunas em `companies`

- `slug` — identificador único do tenant
- `owner_user_id` — responsável pelo tenant
- `onboarding_step` / `onboarding_completed_at` — progresso do onboarding

### Compatibilidade retroativa

Empresas existentes recebem:

- `onboarding_step = completed`
- Assinatura **Enterprise** ativa por 1 ano
- Slug `empresa-padrao` para id=1

```bash
php database/run_migration.php
```

---

## Fluxos

### Onboarding (novos tenants)

```
Login + empresa selecionada
  → onboarding incompleto?
      → GET /onboarding (dados da empresa)
      → POST /onboarding/company
      → GET /onboarding/plan
      → POST /onboarding/plan → assinatura + onboarding concluído
```

### Assinatura inativa

`SubscriptionMiddleware` redireciona para `/subscription` quando status não é utilizável (`trialing`, `active`, `past_due`).

### Cobrança recorrente (cron)

```bash
php scripts/saas_process_billing.php
```

Gera faturas pendentes para assinaturas com `current_period_end` vencido. Pagamento simulado via botão na tela **Assinatura** (ambiente de desenvolvimento).

---

## Arquivos principais

| Camada | Arquivos |
|--------|----------|
| Models | `Plan`, `Subscription`, `SubscriptionInvoice` |
| Repositories | `PlanRepository`, `SubscriptionRepository`, `SubscriptionInvoiceRepository` |
| Services | `TenantService`, `PlanService`, `SubscriptionService`, `PlanLimitService`, `OnboardingService`, `SubscriptionBillingService` |
| Middleware | `OnboardingMiddleware`, `SubscriptionMiddleware` |
| Controllers | `OnboardingController`, `SubscriptionController` |
| Views | `onboarding/company`, `onboarding/plan`, `subscription/show` |
| Exception | `PlanLimitExceededException` |

### Integração de limites

- `CustomerService::create` — `customers_max`
- `ProductService::create` — `products_max`

---

## Segurança e isolamento

- Todo acesso a dados de negócio permanece filtrado por `company_id` (trait `CompanyScope`).
- Onboarding e assinatura exigem empresa selecionada na sessão.
- Rotas de onboarding/assinatura liberadas no `PermissionMiddleware` para qualquer usuário autenticado da empresa.

---

## Próximos passos (evolução)

- Gateway real para cobrança da plataforma (reutilizar padrão `app/Integrations/Payment/`).
- Cadastro público de novo tenant (signup).
- Limites em pedidos (`orders_month_max`) no `OrderService`.
- Papéis por empresa (hoje RBAC é global por usuário).
