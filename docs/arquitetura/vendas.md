# Vendas (Orders)

## 1. Visão geral do módulo

Registro de vendas com itens, baixa automática de estoque (produtos físicos), geração de conta a receber e parcelamento opcional (2–24 parcelas). Disponível na interface web e na API REST.

## 2. Fluxo funcional

### Criação de venda

```
OrderController@store / ApiOrderController@store
  → OrderService::placeOrder(customerId, lines, installmentCount)
    → valida cliente e itens
    → FOR UPDATE em produtos (ordem por product_id — evita deadlock)
    → verifica estoque (exceto type=service)
    → INSERT orders + order_items (unit_price congelado)
    → StockService::apply('saida', ...) por item físico
    → InstallmentService::generateForOrder (se parcelas ≥ 2)
    → AccountsReceivableService::createFromApprovedOrder
    → COMMIT + auditoria (venda, conta_receber, parcelamento, saida_estoque)
```

### Consulta

- Web: `/orders`, `/orders/show`, `/orders/create`
- API: `GET /api/orders` com paginação e filtros `customer_id`, `date_from`, `date_to`

### Cancelamento

Ver `docs/arquitetura/` (regras em `OrderCancelService`) e `docs/regras-negocio/cancelamentos.md`.

## 3. Estrutura de banco relacionada

| Tabela | Campos relevantes |
|--------|-------------------|
| `orders` | `customer_id`, `user_id`, `company_id`, `total_amount`, `status`, `canceled_by`, `canceled_at` |
| `order_items` | `order_id`, `product_id`, `quantity`, `unit_price`, `subtotal` |
| `products` | Estoque e preço no momento da venda |
| `accounts_receivable` | Gerada na venda |
| `installments` | Se parcelado |
| `stock_movements` | Saídas tipo `saida` ref `order` |

Status em `orders`: `pending`, `paid`, `canceled`, `refunded`. O fluxo atual grava **`paid`** na criação (`OrderService` + auditoria).

## 4. Services envolvidos

| Service | Papel |
|---------|-------|
| `OrderService` | `placeOrder()` — orquestração transacional |
| `OrderCancelService` | Cancelamento com estorno |
| `StockService` | Saída e devolução de estoque |
| `InstallmentService` | Parcelas 2–24, intervalo 30 dias |
| `AccountsReceivableService` | AR na venda (`DEFAULT_DUE_DAYS`) |
| `PlanLimitService` | Limites SaaS (indireto em outros módulos) |

## 5. Repositories envolvidos

- `OrderRepository`, `OrderItemRepository`
- `CustomerRepository`, `ProductRepository`
- `StockMovementRepository`
- Repositórios financeiros usados indiretamente via services de AR/parcelas

## 6. Controllers envolvidos

| Controller | Ações |
|------------|-------|
| `OrderController` | `index`, `show`, `create`, `store`, `cancel` |
| `ApiOrderController` | `index`, `store`, `cancel` |

ACL: módulo `vendas` — cancelar = ação `excluir` (`RoutePermissionMap`).

## 7. Regras de negócio

- Pelo menos um item; quantidade positiva; produtos duplicados no payload são **mesclados** (`normalizeLines`).
- Serviços (`type=service`) não consomem estoque.
- Preço unitário copiado para `order_items` no momento da venda (integridade histórica).
- Parcelamento: mínimo 2, máximo 24 (`InstallmentService`).
- Venda à vista: `installment_count` = 1 (default); vencimento AR +30 dias.
- Com parcelas: vencimento da AR alinhado à primeira parcela.

## 8. Fluxo de dados

```
Form/JSON → Controller → OrderService
  → Repositories (PDO transaction)
  → products.stock atualizado via StockService
  → accounts_receivable + installments
  → Audit::record (após commit)
```

## 9. Pontos críticos

- Transação única: falha em qualquer etapa faz rollback completo.
- Ordenação de `product_id` antes dos locks reduz risco de deadlock.
- Cancelamento bloqueado se AR paga ou parcelas/recebimentos já quitados.
- Status `pending` existe no schema mas o fluxo de criação usa `paid`.

## 10. Dependências

- Módulos: clientes, produtos, estoque, financeiro
- `App\Helpers\Money`, `App\Helpers\Audit`
- Views: `app/Views/orders/`, JS `public/assets/js/order_create.js`

## 11. Possíveis melhorias futuras

- Fluxo explícito `pending` → confirmação → `paid` se houver aprovação comercial.
- Reserva de estoque antes do pagamento.
- Idempotência na API (`Idempotency-Key`).
- Refatorar transação para `Database::transaction()`.
