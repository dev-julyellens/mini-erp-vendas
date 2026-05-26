# Implementação — Fluxo multiempresa (gestão)

## Entregue

- Vínculos `user_companies` com papel e status (`019_user_companies_roles.sql`)
- UI `/user-companies` (vincular, papel, ativar/desativar, remover)
- `company_role` na sessão + `TenantContextMiddleware`
- ACL por empresa via `CompanyRoleService` + `TenantContextService`
- Troca de empresa existente reforçada (`CompanyAuthService`)

## Mapeamento ACL (papel na empresa → papel efetivo)

| Papel empresa | ACL efetivo |
|---------------|-------------|
| owner, admin | admin (tenant) |
| manager | vendedor |
| employee | estoque |

Administrador global (`users.role = admin`) mantém acesso total.

## Arquivos principais

- `app/Services/UserCompanyService.php`
- `app/Repositories/UserCompanyRepository.php`
- `app/Middleware/TenantContextMiddleware.php`
- `app/Views/user-companies/index.php`

Ver também: `docs/arquitetura/multiempresa.md`.
