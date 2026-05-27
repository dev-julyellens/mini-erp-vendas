# Permissões (ACL)

## 1. Visão geral do módulo

Controle de acesso baseado em **papéis** (`users.role`) e matriz **módulo × ação** persistida em `permissions` / `role_permissions`. Rotas HTTP mapeadas em `RoutePermissionMap`; verificação em `PermissionMiddleware` via `PermissionService`.

**Multiempresa:** com empresa selecionada, o papel efetivo para ACL é calculado por `TenantContextService` a partir de `user_companies.role` (`CompanyRoleService::effectiveAclRole`). O papel global `admin` continua com bypass total.

## 2. Fluxo funcional

1. Usuário autenticado com `role` na sessão.
2. Request `METHOD + PATH` resolvido em `RoutePermissionMap::resolve()`.
3. Se mapeado: `PermissionService::authorizeRoute(module, action)`.
4. Se não mapeado: rota liberada para qualquer autenticado (ex.: `/`, `/reports` índice, notificações).
5. `role=admin` → bypass total em `PermissionService::can()`.

## 3. Estrutura de banco relacionada

| Tabela | Conteúdo |
|--------|----------|
| `permissions` | 6 módulos × 4 ações = 24 registros base |
| `role_permissions` | Vínculo role → permission_id |
| `users.role` | `admin`, `vendedor`, `financeiro`, `estoque` |

Módulos: `produtos`, `clientes`, `vendas`, `estoque`, `financeiro`, `usuarios`.

Ações: `visualizar`, `criar`, `editar`, `excluir`.

## 4. Services envolvidos

| Service | Função |
|---------|--------|
| `PermissionService` | `can()`, `authorizeRoute()`, cache em request |
| `TenantContextService` | Papel efetivo na empresa ativa |
| `CompanyRoleService` | Papéis `owner`/`admin`/`manager`/`employee` e mapeamento ACL |
| `PlatformAdminService` | Gestão global (empresas, usuários, SaaS) |
| `RoutePermissionMap` | Mapa estático rota → [módulo, ação] |

## 5. Repositories envolvidos

- `PermissionRepository` — carrega permissões por role

## 6. Controllers envolvidos

Todos os controllers protegidos passam pelo middleware; não há lógica ACL duplicada obrigatória nos controllers (alguns services checam role para operações sensíveis, ex. backup).

Exemplos de mapeamento:

| Rota | Módulo | Ação |
|------|--------|------|
| `POST /orders/cancel` | vendas | excluir |
| `POST /stock-movements/store` | estoque | criar |
| `GET /finance` | financeiro | visualizar |
| `GET /audit-logs` | usuarios | visualizar |

## 7. Regras de negócio

### Seeds por role (migration `002`)

| Role | Acesso |
|------|--------|
| **admin** | Tudo (código, não só seed) |
| **vendedor** | Clientes e vendas (criar/editar); produtos/estoque só ver |
| **financeiro** | Financeiro completo; clientes/vendas/produtos só ver |
| **estoque** | Estoque completo; produtos ver/editar; vendas só ver |

Cancelar venda = `vendas.excluir`.

Backup restore = `usuarios.excluir`.

## 8. Fluxo de dados

```
PermissionMiddleware
  → RoutePermissionMap::resolve(method, path)
  → PermissionService::authorizeRoute()
  → PermissionRepository (se não admin)
  → HTTP 403 ou continua para Router
```

UI: `App\Helpers\Permission` espelha `can()` para esconder botões.

## 9. Pontos críticos

- Rotas **sem entrada no mapa** ficam abertas a qualquer usuário logado — inclui `GET /reports` (hub), `GET /`, notificações.
- API usa o **mesmo mapa** que a web.
- Alterar ACL exige migration ou script SQL + possível atualização do mapa de rotas novas.

### Política de rotas novas (opt-in)

1. Toda rota que altera dados ou expõe dados sensíveis **deve** ter entrada em `RoutePermissionMap`.
2. Rotas públicas de leitura genérica (hub, home) podem ficar sem mapa — documentar exceção no PR.
3. No checklist de PR: *“Rota nova mapeada em `RoutePermissionMap`?”* (sim/não + justificativa).

## 9.1 Checklist de PR — permissões

- [ ] `RoutePermissionMap` atualizado para rotas novas que exigem ACL
- [ ] Botões/links na UI usam `Permission::can()` quando aplicável
- [ ] API: mesmo módulo/ação da rota web equivalente

## 10. Dependências

- Autenticação (`AuthMiddleware`) obrigatória antes
- `docs/implementacoes/02-permissao.md` — histórico da feature

## 11. Possíveis melhorias futuras

- ACL dinâmico por usuário (não só por role).
- Cobrir todas as rotas no mapa (princípio deny-by-default).
- Tela administrativa para editar permissões.
- Testes automatizados por role × rota.
