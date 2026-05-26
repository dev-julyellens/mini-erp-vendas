# Regras de negócio — Cancelamentos

## Escopo

Cancelamento de **vendas** (`orders`) via web (`POST /orders/cancel`) ou API (`POST /api/orders/cancel`).

Implementação: `OrderCancelService::cancel()`.

## Pré-condições

- Usuário autenticado.
- Permissão `vendas.excluir`.
- Pedido existente e não já cancelado.
- Pedido com itens.

## Bloqueios (não cancelável)

- Pedido já com status `canceled`.
- Conta a receber já **paga** (`paid`).
- Existem **pagamentos** registrados na AR.
- Existem **parcelas pagas**.

Mensagens via `ValidationException` com campos específicos.

## Efeitos do cancelamento (transação única)

1. Devolução de estoque para itens físicos (`devolucao`, ref. pedido).
2. `orders.status = canceled`, `canceled_by`, `canceled_at`.
3. Cancelamento de parcelas em aberto.
4. Cancelamento de AR em aberto.
5. Auditoria: `cancelamento_venda`, `entrada_estoque` (por produto).
6. Notificação de cancelamento (`NotificationService`) quando configurado.

## Pós-cancelamento

- Pedido **travado** para alterações (`Order::isLocked()`).
- Itens e preços históricos permanecem para auditoria.
- Estoque refletido após devolução.

## Diferença de status

- Schema suporta `refunded`; fluxo principal de cancelamento usa `canceled`.
- Status `pending` no pedido não é usado no fluxo de criação atual (pedido nasce `paid`).

## Referências

- `app/Services/OrderCancelService.php`
- `docs/implementacoes/05-cancelamento-venda.md`
- `docs/arquitetura/vendas.md`
