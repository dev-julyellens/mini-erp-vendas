# 11 — Dashboard gerencial

## Objetivo

Painel inicial moderno com indicadores, gráficos (Chart.js) e listas resumidas, mantendo atalhos e alertas já existentes.

## Indicadores e listas

| Bloco | Conteúdo | Permissão |
|-------|----------|-----------|
| Faturamento hoje / vendas no mês | Pedidos `paid` | vendas · visualizar |
| Total de vendas / clientes | Contadores | vendas / clientes |
| Contas vencidas | Quantidade + valor em aberto + tabela resumo | financeiro · visualizar |
| Estoque baixo | Card + lista (até 8 itens) | produtos ou estoque |
| Gráficos | Faturamento diário (14d), vendas mensais (12m), top produtos (30d) | vendas · visualizar |

## Gráficos

- **Chart.js** 4.x (CDN), script dedicado `public/assets/js/dashboard.js`
- Dados injetados via `<script type="application/json">` (sem lógica de negócio na view além de serialização)
- Bootstrap 5 + classes em `app.css` (`.dashboard-chart-wrap`)

## Arquitetura

- `DashboardController` — HTTP apenas
- `DashboardService` — orquestração e preenchimento de séries temporais
- `DashboardRepository` — consultas agregadas do painel
- Reutiliza `ReportRepository` (top produtos), `ProductRepository` (estoque baixo), `AccountsReceivableRepository` (vencidas)

Índices de performance: migration `010_report_indexes.sql` (pedidos e produtos).

## Verificação manual

1. Acessar `/` autenticado — cards e gráficos conforme perfil.
2. Conferir **faturamento hoje** e **vendas no mês** com pedidos pagos no banco.
3. Gráfico **faturamento diário** — últimos 14 dias com linha contínua (dias sem venda = zero).
4. Gráfico **vendas mensais** — barras dos últimos 12 meses.
5. **Produtos mais vendidos** — barras horizontais (30 dias).
6. **Estoque baixo** — lista e contador; link para `/reports/low-stock` se houver mais itens.
7. **Contas vencidas** — tabela e card (usuário com financeiro).
8. Atalhos (novo cliente, produto, vendas, relatórios) permanecem funcionais.
9. Usuário sem `vendas` não vê gráficos de vendas; sem `financeiro` não vê bloco de vencidas.
