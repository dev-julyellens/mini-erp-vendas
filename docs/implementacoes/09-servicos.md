# 09 — Serviços

## Objetivo

Atender empresas de serviço com cadastro dedicado, venda de serviços e vendas híbridas (produto + serviço), sem quebrar estoque nem vendas existentes.

## Modelo

Serviços são registros na tabela `products` com `type = 'service'` (mesma base do catálogo, compatível com `order_items`).

| Campo | Descrição |
|-------|-----------|
| `price` | Valor padrão usado na venda |
| `estimated_time_minutes` | Tempo estimado opcional (minutos) |
| `stock` / `min_stock` | Sempre zero — sem controle de estoque |

## Regras de negócio

- **Estoque:** `StockService` bloqueia movimentações em serviços; vendas não validam saldo nem geram saída de estoque.
- **Cancelamento:** devolução de estoque apenas para itens do tipo produto.
- **Valor na venda:** preço do catálogo copiado para `order_items.unit_price` (histórico preservado).

## Rotas

| Método | Rota | Descrição |
|--------|------|-----------|
| GET | `/services` | Listagem de serviços |
| GET/POST | `/services/create`, `/services/store` | Novo serviço |
| GET/POST | `/services/edit`, `/services/update` | Editar serviço |
| POST | `/services/delete` | Excluir serviço |

Também é possível cadastrar serviço em `/products` escolhendo tipo **Serviço**.

Vendas em `/orders/create` aceitam produtos e serviços na mesma venda.

## Migration

```bash
php database/run_migration.php
```

Arquivo: `database/migrations/009_add_service_estimated_time.sql`

## Verificação manual

1. Rodar migration e abrir `/services` — listagem com valor padrão e tempo estimado.
2. Criar serviço com SKU `SRV-TEST` e tempo 45 min.
3. Em `/orders/create`, adicionar um produto e um serviço na mesma venda — total correto, venda concluída.
4. Conferir `/orders/show` — coluna Tipo com badges Produto/Serviço.
5. Cancelar venda híbrida — estoque do produto devolvido; serviço sem movimentação.
6. Tentar movimentação de estoque em serviço em `/stock-movements/create` — mensagem de bloqueio.
