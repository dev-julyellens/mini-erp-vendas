# Implementação — Gestão de usuários

## Entregue

- CRUD administrativo em `/admin/users`
- Reset de senha administrativo
- Perfil (`/profile`) e alteração de senha
- `UserService` + extensão de `UserRepository`

## Arquivos principais

- `app/Controllers/UserController.php`
- `app/Controllers/ProfileController.php`
- `app/Services/UserService.php`
- `app/Views/users/*`, `app/Views/profile/*`

## Permissão

Rotas admin exigem `users.role = admin` via `PlatformAdminService`. Rotas de perfil liberadas no `PermissionMiddleware` (skip).
