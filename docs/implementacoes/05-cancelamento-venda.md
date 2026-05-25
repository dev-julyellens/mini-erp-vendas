# Implementação: Cancelamento de venda

**Prompt de origem:** `.cursor/prompts/5-cancelamento-venda.md`  
**Data de referência:** maio/2026  
**Escopo:** Status de vendas, cancelamento transacional com devolução de estoque, bloqueio de alterações e auditoria.

---

## Visão geral

Vendas passam a ter status (`pending`, `paid`, `canceled`, `refunded`). O cancelamento devolve o estoque dos itens, registra o usuário responsável e impede novas alterações na venda.

### Fluxo

```
Detalhe da venda → modal de confirmação → OrderCancelService::cancel()
  → devolução (stock_movements tipo devolucao) + UPDATE orders.status = canceled
  → auditoria cancelamento_venda + entrada_estoque
```

---

## Arquivos criados

| Arquivo | Responsabilidade |
|---------|------------------|
| `database/migrations/005_add_order_status.sql` | Colunas `status`, `canceled_by`, `canceled_at` e novas ações de auditoria |
| `app/Services/OrderCancelService.php` | Regras de cancelamento, transação e logs |
| `docs/implementacoes/05-cancelamento-venda.md` | Este documento |

---

## Arquivos alterados

| Arquivo | Alteração |
|---------|-----------|
| `database/database.sql` | Schema de `orders` e `audit_logs` para instalação nova |
| `app/Models/Order.php` | Status, helpers `canCancel()` / `isLocked()` |
| `app/Repositories/OrderRepository.php` | `findByIdForUpdate`, `markCanceled`, insert com status |
| `app/Services/OrderService.php` | Auditoria de venda inclui `status: paid` |
| `app/Services/AuditService.php` | Labels `cancelamento_venda`, `entrada_estoque` |
| `app/Controllers/OrderController.php` | Ação `cancel` |
| `app/Controllers/ApiOrderController.php` | `POST /api/orders/cancel` e campo `status` na listagem |
| `app/Views/orders/show.php` | Badge de status, modal de confirmação, botão cancelar |
| `app/Views/orders/index.php` | Coluna status |
| `public/index.php` | Rotas de cancelamento |
| `app/Services/RoutePermissionMap.php` | ACL `vendas` + `excluir` |

---

## Status da venda

| Status | Descrição | Pode cancelar? |
|--------|-----------|----------------|
| `pending` | Pendente | Sim |
| `paid` | Paga (padrão em novas vendas) | Sim |
| `canceled` | Cancelada | Não |
| `refunded` | Reembolsada | Não |

Vendas existentes recebem `paid` via migration (compatibilidade retroativa).

---

## Regras de negócio

- Cancelamento só para status `pending` ou `paid`.
- Devolução de estoque via `StockService::registerReturn()` (tipo `devolucao`, referência `order`).
- `canceled_by` e `canceled_at` preenchidos no cancelamento.
- `updateTotal` e futuras edições bloqueadas por status (`canceled` / `refunded`).
- Permissão: módulo `vendas`, ação `excluir` (ex.: admin; vendedor não cancela).

---

## Auditoria

| Ação | Entidade | Quando |
|------|----------|--------|
| `cancelamento_venda` | vendas | Após commit do cancelamento |
| `entrada_estoque` | estoque | Por item devolvido |

---

## API

`POST /api/orders/cancel` — corpo JSON:

```json
{ "order_id": 1 }
```

Resposta de sucesso: `{ "success": true, "order_id": 1, "status": "canceled" }`

---

## Como aplicar

```bash
php database/run_migration.php
```

Ou apenas a migration 005 em banco existente:

```bash
psql -U postgres -d mini_erp_vendas -f database/migrations/005_add_order_status.sql
```

---

## Testes manuais

- [ ] Registrar venda → status `Paga` na listagem e detalhe
- [ ] Cancelar com usuário admin → estoque restaurado, status `Cancelada`
- [ ] Tentar cancelar novamente → mensagem de erro
- [ ] Logs em Auditoria: `cancelamento_venda` e `entrada_estoque`
- [ ] Vendedor não vê botão de cancelar (sem permissão `excluir`)
- [ ] Movimentações de estoque com tipo Devolução referenciando o pedido
