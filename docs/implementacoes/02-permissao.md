# Implementação: Permissões (ACL)

**Prompt de origem:** `.cursor/prompts/2-permissao.md`  
**Data de referência:** maio/2026  
**Escopo:** ACL por perfil (`role`), middleware de autorização, validação no backend e ocultação de menus/ações na interface.

---

## Visão geral

Sistema de permissões desacoplado em **Service + Repository**, com tabelas `permissions` e `role_permissions`. O perfil **`admin`** possui acesso total (bypass no código). Demais perfis recebem permissões via seed na migration.

### Fluxo

```
Request → AuthMiddleware (sessão)
       → PermissionMiddleware (módulo + ação da rota)
       → Router → Controller → Service → Repository
```

Views usam `App\Helpers\Permission` apenas para **ocultar** links e botões; a proteção real está no middleware.

---

## Arquivos criados

| Arquivo | Responsabilidade |
|---------|------------------|
| `app/Middleware/PermissionMiddleware.php` | Bloqueia rotas sem permissão (403) |
| `app/Services/PermissionService.php` | Regras ACL, cache por role, bypass admin |
| `app/Services/RoutePermissionMap.php` | Mapeamento rota HTTP → módulo + ação |
| `app/Repositories/PermissionRepository.php` | Consultas a `permissions` / `role_permissions` |
| `app/Helpers/Permission.php` | API simples para views (`can`, `canView`) |
| `database/migrations/002_create_permissions.sql` | Tabelas, índices e seeds por perfil |
| `docs/implementacoes/02-permissao.md` | Este documento |

---

## Arquivos alterados

| Arquivo | Alteração |
|---------|-----------|
| `public/index.php` | Chamada a `PermissionMiddleware::handle()` após auth |
| `database/run_migration.php` | Executa todas as migrations `*.sql` em ordem |
| `database/database.sql` | Tabelas ACL + seeds em instalação nova |
| `app/Views/layouts/main.php` | Menu filtrado por `Permission::canView()` |
| `app/Views/products/index.php` | Botões criar/editar/excluir condicionais |
| `app/Views/customers/index.php` | Botões criar/editar/excluir condicionais |
| `app/Views/orders/index.php` | Botão nova venda condicional |

---

## Tabelas

### `permissions`

| Coluna | Tipo | Observação |
|--------|------|------------|
| `id` | SERIAL PK | |
| `module` | VARCHAR(50) | `produtos`, `clientes`, `vendas`, `estoque`, `financeiro`, `usuarios` |
| `action` | VARCHAR(50) | `visualizar`, `criar`, `editar`, `excluir` |

UNIQUE (`module`, `action`).

### `role_permissions`

| Coluna | Tipo | Observação |
|--------|------|------------|
| `role` | VARCHAR(50) | Mesmos valores do CHECK em `users.role` (exceto admin — bypass no código) |
| `permission_id` | INTEGER FK | REFERENCES `permissions(id)` ON DELETE CASCADE |

PRIMARY KEY (`role`, `permission_id`).

---

## Permissões por perfil (seed)

| Perfil | Acesso |
|--------|--------|
| **admin** | Tudo (bypass — não depende de `role_permissions`) |
| **vendedor** | Clientes completo; vendas (sem excluir); produtos e estoque só visualizar |
| **financeiro** | Financeiro completo; clientes, vendas e produtos só visualizar |
| **estoque** | Estoque completo; produtos visualizar/editar; vendas só visualizar |

---

## Mapeamento de rotas

| Rota | Módulo | Ação |
|------|--------|------|
| `GET/POST /customers/*` | clientes | conforme operação |
| `GET/POST /products/*` | produtos | conforme operação |
| `GET/POST /orders/*` | vendas | conforme operação |
| `GET /api/products` | produtos | visualizar |
| `GET /api/orders` | vendas | visualizar |
| `POST /api/orders` | vendas | criar |

Rotas sem entrada no mapa (ex.: `/`, `/logout`) não exigem permissão de módulo além de autenticação.

---

## API para views

```php
use App\Helpers\Permission;

Permission::can('produtos', 'criar');
Permission::canView('clientes'); // atalho para visualizar
```

---

## Como aplicar migration

Banco existente:

```bash
php database/run_migration.php
```

Instalação nova: `database/database.sql` já inclui as tabelas ACL.

---

## Critérios de aceite

| Critério | Status |
|----------|--------|
| Usuário sem permissão não acessa rota | OK (403 no middleware) |
| Menus ocultados corretamente | OK (`main.php`) |
| Middleware funcionando | OK |
| Permissões desacopladas | OK (Service + Repository + tabelas) |
| Admin com acesso total | OK |
| Backend sempre valida | OK (middleware independente da UI) |

---

## Teste manual sugerido

1. Criar usuários de teste com roles `vendedor`, `financeiro` e `estoque`.
2. Logar como **vendedor**: deve ver Clientes e Vendas; Produtos só listagem; sem botão excluir em vendas.
3. Acessar diretamente `POST /products/store` → 403.
4. Logar como **admin**: acesso completo a todas as rotas e menus.

---

## Referências

- Autenticação: `docs/implementacoes/01-autenticacao.md`
- Prompt: `.cursor/prompts/2-permissao.md`
