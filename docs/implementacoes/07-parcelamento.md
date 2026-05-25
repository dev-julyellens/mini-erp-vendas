# 07 — Parcelamento

## Objetivo

Parcelar vendas em múltiplas parcelas com cálculo automático, vencimentos escalonados, baixa manual e status automático.

## Tabela `installments`

| Campo | Descrição |
|-------|-----------|
| `order_id` | Venda vinculada |
| `installment_number` | Número da parcela (1, 2, …) |
| `amount` | Valor da parcela |
| `due_date` | Vencimento |
| `paid_at` | Data/hora da baixa |
| `status` | `pending`, `overdue`, `paid`, `canceled` |

## Regras de negócio

1. **Venda com 2+ parcelas** → divide o total automaticamente (última parcela absorve centavos); vencimentos a cada 30 dias (1ª em D+30).
2. **Venda à vista (1x)** → comportamento anterior: só conta a receber, sem linhas em `installments`.
3. **Status automático** → `pending` vira `overdue` quando `due_date < hoje` (atualizado nas listagens).
4. **Baixa manual** → registra pagamento na conta a receber, fluxo de caixa e marca parcela como `paid`.
5. **Cancelamento de venda** → cancela parcelas abertas; bloqueia se houver parcela paga.
6. **Conta com parcelamento** → recebimento direto na ficha da conta fica bloqueado; usar baixa por parcela.

## Rotas

| Método | Rota | Tela |
|--------|------|------|
| GET | `/finance/installments/overdue` | Contas vencidas |
| GET | `/finance/installments/open` | Parcelas abertas |
| GET | `/finance/installments/history` | Histórico |
| GET/POST | `/finance/installments/pay` | Baixa de parcela |

## Migration

```bash
php database/run_migration.php
```

Arquivo: `database/migrations/007_create_installments.sql`

## Verificação manual

1. Nova venda em 3x → conferir 3 parcelas na ficha da venda; soma dos valores = total da venda.
2. Parcelas abertas → listar pendentes; após vencimento, conferir status **Vencida**.
3. Baixar parcela 1 → fluxo de caixa, conta parcial; baixar demais → conta `paid`.
4. Venda à vista (1x) → sem parcelas; recebimento normal na conta a receber.
5. Cancelar venda parcelada sem pagamentos → parcelas `canceled`.
