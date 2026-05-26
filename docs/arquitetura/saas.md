# SaaS (administração da plataforma)

## Visão tenant vs plataforma

- **Tenant:** `/subscription`, onboarding — empresa logada gerencia própria assinatura.
- **Plataforma:** `/admin/saas` — administrador global vê planos, métricas e vínculos empresa/plano.

## Componentes admin

| Peça | Arquivo |
|------|---------|
| Controller | `SaasAdminController` |
| Service | `SaasAdminService` |
| Repositories | `PlanRepository`, `SubscriptionRepository` |

## Rotas

- `GET /admin/saas` — dashboard (empresas, usuários, assinaturas)
- `GET /admin/saas/subscriptions` — listagem + formulário de plano
- `POST /admin/saas/assign-plan` — `SubscriptionService::subscribeCompany()`

## Tabelas

`plans`, `plan_limits`, `subscriptions`, `subscription_invoices` (migration `017_create_saas.sql`).

## Limites por plano

`PlanLimitService` aplica limites no tenant ativo (`customers_max`, `products_max`, etc.).
