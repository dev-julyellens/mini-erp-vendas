# Regras de negócio — Controle de estoque

## Saldo

- Fonte da verdade operacional: campo `products.stock`.
- Histórico: cada alteração gera registro em `stock_movements` com `stock_before` e `stock_after`.

## Tipos de movimentação

| Tipo | Efeito típico |
|------|----------------|
| `entrada` | Aumenta estoque |
| `saida` | Diminui estoque |
| `devolucao` | Aumenta (retorno) |
| `perda` | Diminui (perda) |
| `ajuste` | Define saldo conforme regra do service |
| `inventario` | Contagem/inventário |

## Automático

- **Venda:** `saida` vinculada ao `order_id`.
- **Cancelamento de venda:** `devolucao` com mesma quantidade dos itens físicos.

## Manual

- Tela `/stock-movements` — permissão `estoque.criar`.
- Observações opcionais (`notes`).
- Usuário registrado em `created_by`.

## Restrições

- Produtos `type=service` **não** podem receber movimentação.
- Quantidade deve ser positiva nos tipos que exigem magnitude absoluta.
- Venda bloqueada se estoque insuficiente (antes da transação).

## Alertas

- Após movimentação que reduz estoque: se `stock <= min_stock`, `NotificationService` cria alerta.
- Relatório `/reports/low-stock` lista produtos no limite.

## Multi-empresa

- Movimentações filtradas via `products.company_id` (tabela de movimentos sem `company_id` próprio).

## Referências

- `app/Services/StockService.php`
- `docs/arquitetura/estoque.md`
- `docs/implementacoes/04-movimentacao-estoque.md`
