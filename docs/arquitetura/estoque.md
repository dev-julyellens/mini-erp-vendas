# Estoque

## 1. Visão geral do módulo

Controle de quantidade em `products.stock` com histórico imutável em `stock_movements`. Movimentações automáticas na venda/cancelamento e manuais pela tela de movimentações. Serviços não participam de estoque.

## 2. Fluxo funcional

### Automático (venda)

`OrderService` → `StockService::apply('saida', productId, qty, 'order', orderId, ...)`

### Automático (cancelamento)

`OrderCancelService` → `StockService::registerReturn()` (tipo `devolucao`)

### Manual

```
GET/POST /stock-movements → StockMovementController
  → StockService::registerManual(type, productId, quantity, notes)
```

### Pós-movimento

`NotificationService::notifyLowStock()` quando `stock <= min_stock`.

### Relatório

`GET /reports/low-stock` — produtos abaixo do mínimo (ACL módulo `estoque`).

## 3. Estrutura de banco relacionada

| Tabela | Detalhe |
|--------|---------|
| `products` | `stock`, `min_stock`, `type` (product/service) |
| `stock_movements` | `type`, `quantity`, `stock_before`, `stock_after`, `reference_type`, `reference_id`, `notes`, `created_by` |

Tipos permitidos (`StockService::TYPES`):

`entrada`, `saida`, `ajuste`, `devolucao`, `perda`, `inventario`

**Nota:** `stock_movements` não possui `company_id`; escopo via `products.company_id` no `StockMovementRepository`.

## 4. Services envolvidos

| Service | Função |
|---------|--------|
| `StockService` | `apply()`, `registerManual()`, `registerReturn()` |
| `OrderService` / `OrderCancelService` | Disparam movimentações |
| `NotificationService` | Alerta estoque baixo |
| `ReportService` | Relatório low-stock |

## 5. Repositories envolvidos

- `StockMovementRepository` — insert e listagem
- `ProductRepository` — leitura/atualização de `stock` com lock quando necessário

## 6. Controllers envolvidos

| Controller | Rotas |
|------------|-------|
| `StockMovementController` | `/stock-movements`, `/create`, `/store` |
| `ReportController` | `/reports/low-stock` (+ export) |

## 7. Regras de negócio

- Quantidade sempre positiva nos tipos `entrada`, `saida`, `devolucao`, `perda`; efeito definido pelo tipo.
- `ajuste` e `inventario` definem saldo alvo (lógica no service).
- Serviço (`type=service`): `ValidationException` ao tentar movimentar.
- Venda valida estoque **antes** da transação de baixa.
- Movimentação e atualização de `products.stock` na **mesma transação** PDO.

## 8. Fluxo de dados

```
StockService::apply
  → INSERT stock_movements
  → UPDATE products SET stock = ...
  → (opcional) NotificationService
```

Referências comuns: `reference_type='order'`, `reference_id=<orderId>`.

## 9. Pontos críticos

- Concorrência: vendas simultâneas no mesmo SKU dependem de `FOR UPDATE` em `OrderService`.
- Inventário/ajuste manual exige permissão `estoque.criar`.
- Auditoria de estoque em vendas/cancelamentos via `Audit::record` (`saida_estoque`, `entrada_estoque`).

## 10. Dependências

- Módulo produtos (`products`)
- Módulo vendas (saída/devolução automática)
- `app/Views/stock/`

## 11. Possíveis melhorias futuras

- `company_id` direto em `stock_movements`.
- Lote/série e múltiplos depósitos.
- Custo médio ponderado integrado ao financeiro.
- Job assíncrono para alertas de estoque baixo em alto volume.
