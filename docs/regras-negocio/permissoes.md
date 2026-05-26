# Regras de negócio — Permissões

## Modelo

- **Papel** (`users.role`): admin, vendedor, financeiro, estoque.
- **Permissão**: par `(módulo, ação)`.
- **Autorização**: middleware em toda requisição autenticada mapeada.

## Módulos e ações

| Módulo | Ações |
|--------|-------|
| produtos | visualizar, criar, editar, excluir |
| clientes | idem |
| vendas | idem |
| estoque | idem |
| financeiro | idem |
| usuarios | idem (auditoria, backups, logs) |

## Admin

- `role === 'admin'` → acesso irrestrito via código (`PermissionService`), independente de `role_permissions`.

## Mapeamentos importantes

| Operação | Permissão exigida |
|----------|-------------------|
| Registrar venda | vendas.criar |
| Cancelar venda | vendas.excluir |
| Movimentar estoque | estoque.criar |
| Receber pagamento | financeiro.criar |
| Excluir produto | produtos.excluir |
| Restaurar backup | usuarios.excluir |

## Rotas sem ACL explícita

Qualquer usuário autenticado pode acessar (exemplos):

- `GET /` (dashboard)
- `GET /reports` (índice de relatórios — sub-rotas têm ACL)
- Rotas de notificações
- Fluxos de onboarding, LGPD e assinatura (com skip lists nos middlewares)

## UI vs servidor

- Views podem ocultar botões com `Permission::can()`.
- **Bloqueio efetivo** sempre no `PermissionMiddleware` — não confiar só na UI.

## API

- Mesmas regras da interface web (`RoutePermissionMap` inclui rotas `/api/*`).

## Referências

- `database/migrations/002_create_permissions.sql`
- `app/Services/RoutePermissionMap.php`
- `docs/arquitetura/permissoes.md`
- `docs/implementacoes/02-permissao.md`
