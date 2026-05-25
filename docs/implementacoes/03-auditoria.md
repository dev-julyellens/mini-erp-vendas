# Implementação: Auditoria

**Prompt de origem:** `.cursor/prompts/3-auditoria.md`  
**Data de referência:** maio/2026  
**Escopo:** Logs de auditoria desacoplados, registro automático em operações críticas e tela administrativa de consulta.

---

## Visão geral

Auditoria implementada em **Service + Repository + Helper**, sem acoplamento aos controllers. Os services de domínio registram alterações via `App\Helpers\Audit` após operações bem-sucedidas.

### Fluxo

```
Service (produto/cliente/venda/auth) → Audit::record() → AuditService → AuditRepository → audit_logs
Consulta: AuditLogController → AuditService::searchLogs() → view com filtros
```

---

## Arquivos criados

| Arquivo | Responsabilidade |
|---------|------------------|
| `database/migrations/003_create_audit_logs.sql` | Tabela `audit_logs`, índices e constraints |
| `app/Models/AuditLog.php` | Modelo de leitura dos registros |
| `app/Repositories/AuditRepository.php` | Insert e busca paginada com filtros |
| `app/Services/AuditService.php` | Regras de registro, snapshots e consulta |
| `app/Helpers/Audit.php` | API reutilizável para os services |
| `app/Controllers/AuditLogController.php` | Tela administrativa (sem SQL) |
| `app/Views/audit/index.php` | Listagem, filtros e detalhes em modal |
| `docs/implementacoes/03-auditoria.md` | Este documento |

---

## Arquivos alterados

| Arquivo | Alteração |
|---------|-----------|
| `database/database.sql` | Tabela `audit_logs` em instalação nova |
| `app/Services/ProductService.php` | Auditoria criar/editar/excluir produtos e ajuste de estoque |
| `app/Services/CustomerService.php` | Auditoria CRUD de clientes |
| `app/Services/OrderService.php` | Auditoria de venda e saída de estoque (após commit) |
| `app/Services/AuthService.php` | Login, logout, solicitação e redefinição de senha |
| `public/index.php` | Rota `GET /audit-logs` |
| `app/Services/RoutePermissionMap.php` | Permissão `usuarios` + `visualizar` |
| `app/Views/layouts/main.php` | Link Auditoria no menu (perfil com acesso a usuários) |

---

## Tabela `audit_logs`

| Coluna | Tipo | Observação |
|--------|------|------------|
| `id` | SERIAL PK | |
| `user_id` | INTEGER FK | `users(id)` ON DELETE SET NULL |
| `action` | VARCHAR(50) | criar, editar, excluir, login, logout, venda, etc. |
| `entity` | VARCHAR(50) | produtos, clientes, vendas, estoque, usuarios |
| `entity_id` | INTEGER | ID da entidade afetada |
| `old_values` | JSONB | Estado anterior |
| `new_values` | JSONB | Estado novo |
| `ip_address` | VARCHAR(45) | IP do request |
| `user_agent` | TEXT | User-Agent do request |
| `created_at` | TIMESTAMP | Momento do registro |

---

## Entidades auditadas

| Entidade | Operações | Onde |
|----------|-----------|------|
| **produtos** | criar, editar, excluir | `ProductService` |
| **estoque** | editar (ajuste manual), saida_estoque (venda) | `ProductService`, `OrderService` |
| **clientes** | criar, editar, excluir | `CustomerService` |
| **vendas** | venda | `OrderService` (após commit da transação) |
| **usuarios** | login, logout, solicitar/redefinir senha | `AuthService` |

Senhas e hashes **nunca** são gravados nos logs.

Falha ao gravar log **não interrompe** a operação de negócio (erro registrado em `error_log`).

---

## Consulta administrativa

- **Rota:** `GET /audit-logs`
- **Permissão:** módulo `usuarios`, ação `visualizar` (admin tem bypass)
- **Filtros:** usuário, entidade, período (data de/até)
- **Paginação:** 20 registros por página (partial compartilhado `partials/pagination.php`)
- **Filtro de entidade:** apenas valores da whitelist (`AuditService::ENTITIES`)

---

## Robustez (revisão pós-implementação)

| Correção | Detalhe |
|----------|---------|
| `AuditService::record()` | `try/catch` — falha de auditoria não propaga exceção |
| `searchLogs()` | Whitelist de entidade via `normalizeEntityFilter()` |
| Labels | Centralizados em `AuditService::ACTION_LABELS` / `ENTITY_LABELS` |
| IP | Validação com `filter_var(FILTER_VALIDATE_IP)` |
| View | Paginação reutilizada; IP oculto em telas &lt; lg (visível no modal) |

---

## Como aplicar a migration

```bash
php database/run_migration.php
```

---

## Verificação manual sugerida

1. Login como admin → criar/editar produto e cliente → conferir registros em `/audit-logs`
2. Registrar uma venda → ver log `venda` em vendas e `saida_estoque` em estoque
3. Logout/login → logs em `usuarios`
4. Filtrar por usuário e intervalo de datas
