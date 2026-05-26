# Usuários e gestão administrativa

## Camadas

| Camada | Arquivo |
|--------|---------|
| Controller | `UserController`, `ProfileController` |
| Service | `UserService` |
| Repository | `UserRepository` |

## Rotas (plataforma)

- `GET/POST /admin/users` — CRUD e ativação
- `GET/POST /admin/users/reset-password` — reset administrativo
- Acesso: `PlatformAdminService` (`users.role = admin`)

## Perfil

- `GET /profile`, `POST /profile/update` — qualquer autenticado
- `GET/POST /profile/password` — alteração com senha atual

## Regras

- E-mail único (`users.email`)
- Senha com `PasswordPolicyService` + `password_hash`
- Usuário inativo não autentica (`Auth::user()`)
- Sem exclusão física; desativação via `active`

## Papéis globais

`admin`, `vendedor`, `financeiro`, `estoque` — matriz ACL em `role_permissions`.
