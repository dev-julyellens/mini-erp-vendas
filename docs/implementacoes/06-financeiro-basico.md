# 06 — Financeiro básico

## Objetivo

Controle financeiro mínimo após a venda: contas a receber, recebimentos e fluxo de caixa.

## Tabelas

| Tabela | Função |
|--------|--------|
| `accounts_receivable` | Título gerado por venda (`order_id` único) |
| `payments` | Recebimentos parciais ou totais |
| `cash_flow` | Entrada de caixa por recebimento (`reference_type = payment`) |

### Campos principais

- **Valor:** `accounts_receivable.amount`, `payments.amount`, `cash_flow.amount`
- **Vencimento:** `accounts_receivable.due_date` (padrão: +30 dias na criação)
- **Status da conta:** `pending`, `partial`, `paid`, `canceled`
- **Forma de pagamento:** `dinheiro`, `pix`, `cartao`, `boleto`
- **Data de pagamento:** `payments.paid_at` e `cash_flow.occurred_at`

## Regras de negócio

1. **Venda aprovada** (`OrderService::placeOrder`, status `paid`) → cria conta a receber na mesma transação.
2. **Recebimento** (`PaymentService::receive`) → em transação:
   - insere `payments`;
   - atualiza status da conta (`partial` ou `paid`);
   - lança `cash_flow` tipo `entrada`.
3. **Cancelamento de venda** → cancela conta aberta (`pending`/`partial`) sem recebimentos. Bloqueia se a conta estiver `paid` ou se houver qualquer pagamento registrado (`partial` com recebimentos).

## Rotas

| Método | Rota | Permissão |
|--------|------|-----------|
| GET | `/finance` | financeiro / visualizar |
| GET | `/finance/cash-flow` | financeiro / visualizar |
| GET | `/finance/accounts-receivable` | financeiro / visualizar |
| GET | `/finance/accounts-receivable/show` | financeiro / visualizar |
| GET/POST | `/finance/accounts-receivable/receive` | financeiro / criar |

## Migration

```bash
php database/run_migration.php
```

Arquivo: `database/migrations/006_create_financial.sql`

## Verificação manual

1. Registrar uma venda → conferir nova linha em Contas a receber (status Pendente).
2. Registrar recebimento (PIX, valor total) → conta fica Recebida; fluxo de caixa com entrada.
3. Recebimento parcial → status Parcial; segundo recebimento completa para `paid`.
4. Cancelar venda sem recebimento → conta `canceled`; estoque devolvido (fluxo existente).
5. Dashboard financeiro exibe totais a receber, saldo e recebido no mês.
