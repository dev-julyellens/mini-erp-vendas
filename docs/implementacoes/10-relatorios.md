# 10 — Relatórios gerenciais

## Objetivo

Relatórios profissionais com filtros reutilizáveis, paginação, consultas performáticas e exportação PDF / Excel.

## Relatórios

| Relatório | Rota | Permissão |
|-----------|------|-----------|
| Vendas por período | `/reports/sales-period` | vendas · visualizar |
| Vendas por cliente | `/reports/sales-customer` | vendas · visualizar |
| Vendas por produto | `/reports/sales-product` | vendas · visualizar |
| Produtos mais vendidos | `/reports/top-products` | vendas · visualizar |
| Estoque mínimo | `/reports/low-stock` | estoque · visualizar |
| Fluxo de caixa | `/reports/cash-flow` | financeiro · visualizar |

Hub: `/reports` (cards conforme permissões do usuário).

## Filtros

- Datas `date_from` / `date_to` (padrão: mês atual)
- Cliente, produto, categoria (conforme relatório)
- Status do pedido (vendas; padrão: `paid`)
- Tipo de movimentação (fluxo de caixa)

Serviço reutilizável: `ReportFilterService` + modelo `ReportFilter`.

## Exportação

Em cada relatório, botões **PDF** e **Excel** (`format=pdf` ou `format=xlsx`).

Dependências (Composer):

- `dompdf/dompdf`
- `phpoffice/phpspreadsheet`

## Arquitetura

- `ReportController` — HTTP apenas
- `ReportService` — orquestração
- `ReportRepository` — SQL agregado (sem `SELECT *`)
- `ReportExportService` — PDF / XLSX

## Migration

```bash
php database/run_migration.php
```

Arquivo: `database/migrations/010_report_indexes.sql`

## Verificação manual

1. `composer install` na raiz (se ainda não instalou dependências).
2. Rodar migration de índices.
3. Abrir `/reports` — cards visíveis conforme perfil.
4. **Vendas por período** — filtrar mês, conferir totais e paginação; exportar PDF e Excel.
5. **Vendas por cliente** — filtrar um cliente; exportar.
6. **Produtos mais vendidos** — ordenação por quantidade; exportar.
7. **Estoque mínimo** — listar produtos abaixo do mínimo; exportar.
8. **Fluxo de caixa** — entradas/saídas e saldo do período; exportar.
9. Usuário sem permissão de vendas não acessa `/reports/sales-period` (403).
