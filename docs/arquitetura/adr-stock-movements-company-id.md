# ADR: `company_id` em `stock_movements`

**Status:** Implementado (mai/2026)  
**Contexto:** Fase 5 do [plano de débito técnico](../bugs/plano-implementacao-debito-tecnico.md)

## Problema

A tabela `stock_movements` não tinha `company_id`. O isolamento por empresa dependia sempre de `INNER JOIN products p ON p.id = m.product_id` com `p.company_id = :company_id`. Um SELECT sem o join (ou com join incorreto) poderia expor movimentações de outra empresa.

## Decisão

Adicionar `company_id NOT NULL` em `stock_movements`, preenchido no insert a partir do contexto da empresa (`CompanyScope`) e backfill via `products.company_id` na migration `024_stock_movements_company_id.sql`.

## Consequências

### Prós

- Filtro tenant direto: `WHERE m.company_id = :company_id` sem depender só do join.
- Índice `(company_id, created_at DESC)` melhora listagens por empresa.
- Alinhamento com o padrão das demais tabelas multi-tenant.

### Contras

- Redundância com `products.company_id` (denormalização controlada).
- Migration em bases grandes: `UPDATE` + `NOT NULL` exige janela curta; testar backup/restore antes.
- Todo insert deve informar `company_id` (garantido em `StockMovementRepository`).

## Backfill

```sql
UPDATE stock_movements m
SET company_id = p.company_id
FROM products p
WHERE m.product_id = p.id AND m.company_id IS NULL;
```

Linhas órfãs (produto inexistente) impedem `NOT NULL` — corrigir dados antes de aplicar em produção.

## Queries afetadas

| Local | Alteração |
|-------|-----------|
| `StockMovementRepository::insert` | Inclui `company_id` |
| `StockMovementRepository::search` | `m.company_id = :company_id` (join com `products` mantido só para `product_name`) |

Relatórios e dashboard não consultam `stock_movements` diretamente hoje.

## Rollback

Não automatizado. Em emergência: remover FK/índice e coluna após exportar histórico, se necessário.
