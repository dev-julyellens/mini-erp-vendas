# Multiempresa (tenant)

## Contexto

Cada registro de negócio possui `company_id`. O tenant ativo vem da sessão (`auth_user.company_id`) ou do JWT na API.

## Componentes

| Peça | Arquivo | Função |
|------|---------|--------|
| Contexto | `app/Helpers/CompanyContext.php` | `id()`, `requireId()`, `hasSelected()` |
| Sessão | `app/Helpers/Auth.php` | `company_id`, `company_name`, `company_role` |
| Login empresa | `app/Services/CompanyAuthService.php` | Pivot `user_companies`, troca de empresa |
| Tenant | `app/Services/TenantService.php` | Validação de acesso ao tenant |
| Middleware | `app/Middleware/TenantContextMiddleware.php` | Atualiza `company_role` na sessão |
| Escopo SQL | `app/Repositories/Concerns/CompanyScope.php` | Filtro automático por `company_id` |

## Troca de empresa

1. Header → `/select-company` (GET/POST).
2. `AuthService::selectCompany()` valida vínculo ativo em `user_companies`.
3. Sessão recebe empresa e papel (`company_role`).

## Isolamento

Repositórios com trait `CompanyScope` aplicam `WHERE company_id = :company_id` do contexto atual. Gestão global (admin plataforma) usa repositórios sem escopo.

## Vínculo usuário ↔ empresa

Tabela `user_companies`: `role` (`owner`, `admin`, `manager`, `employee`), `active`, timestamps. Migration `019_user_companies_roles.sql`.
